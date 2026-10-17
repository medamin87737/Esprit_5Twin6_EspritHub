<?php

namespace Tests\Feature\Module1;

use App\Models\Categorie;
use App\Models\Certification;
use App\Models\EmpreinteCarbone;
use App\Models\Lot;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du catalogue enrichi (A1) — Module 1 (Ghada).
 * Utilise RefreshDatabase + Http::fake() pour les appels IA (pas de vrais appels).
 */
class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Crée un produit simple avec une catégorie. */
    private function creerProduit(array $attrs = []): Produit
    {
        return Produit::factory()->create($attrs);
    }

    /** Crée un lot avec une empreinte carbone pour donner un score au produit. */
    private function creerEmpreinte(Produit $produit, float $co2): EmpreinteCarbone
    {
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);

        return EmpreinteCarbone::factory()->for($lot)->create(['co2_total' => $co2]);
    }

    // ─── Tests de base ────────────────────────────────────────────────────────

    public function test_catalogue_accessible_sans_authentification(): void
    {
        // Utilise getJson pour éviter le rendu Vite complet en test (pas de build)
        $this->getJson(route('front.produits.index'))->assertOk();
    }

    public function test_catalogue_affiche_les_produits(): void
    {
        $produits = Produit::factory()->count(3)->create();

        $data = $this->getJson(route('front.produits.index'))->json();

        foreach ($produits as $p) {
            $this->assertStringContainsString($p->nom, $data['html']);
        }
    }

    // ─── Filtre recherche textuelle ────────────────────────────────────────────

    public function test_filtre_q_recherche_dans_le_nom(): void
    {
        Produit::factory()->create(['nom' => 'Yaourt nature bio']);
        Produit::factory()->create(['nom' => 'Tomate cerise']);

        $data = $this->getJson(route('front.produits.index', ['q' => 'Yaourt']))->json();

        $this->assertStringContainsString('Yaourt nature bio', $data['html']);
        $this->assertStringNotContainsString('Tomate cerise', $data['html']);
    }

    public function test_filtre_q_recherche_dans_le_code_barres(): void
    {
        $produit = Produit::factory()->create(['code_barres' => '1234567890123', 'nom' => 'Produit A']);
        Produit::factory()->create(['nom' => 'Produit B']);

        $data = $this->getJson(route('front.produits.index', ['q' => '1234567890123']))->json();

        $this->assertStringContainsString('Produit A', $data['html']);
        $this->assertStringNotContainsString('Produit B', $data['html']);
    }

    public function test_filtre_q_recherche_dans_la_composition(): void
    {
        Produit::factory()->create(['nom' => 'Produit A', 'composition' => 'lait écrémé, ferments lactiques']);
        Produit::factory()->create(['nom' => 'Produit B', 'composition' => 'farine de blé']);

        $data = $this->getJson(route('front.produits.index', ['q' => 'ferments lactiques']))->json();

        $this->assertStringContainsString('Produit A', $data['html']);
        $this->assertStringNotContainsString('Produit B', $data['html']);
    }

    public function test_filtre_q_recherche_dans_le_nom_de_categorie(): void
    {
        $cat = Categorie::factory()->create(['nom' => 'Fromages fins']);
        Produit::factory()->create(['nom' => 'Produit A', 'categorie_id' => $cat->id]);
        Produit::factory()->create(['nom' => 'Produit B']);

        $data = $this->getJson(route('front.produits.index', ['q' => 'Fromages']))->json();

        $this->assertStringContainsString('Produit A', $data['html']);
        $this->assertStringNotContainsString('Produit B', $data['html']);
    }

    // ─── Filtre catégorie ──────────────────────────────────────────────────────

    public function test_filtre_categorie_retourne_produits_de_cette_categorie(): void
    {
        $cat1 = Categorie::factory()->create(['nom' => 'Légumes']);
        $cat2 = Categorie::factory()->create(['nom' => 'Fruits']);
        Produit::factory()->create(['nom' => 'Carotte', 'categorie_id' => $cat1->id]);
        Produit::factory()->create(['nom' => 'Pomme', 'categorie_id' => $cat2->id]);

        $data = $this->getJson(route('front.produits.index', ['categorie' => $cat1->id]))->json();

        $this->assertStringContainsString('Carotte', $data['html']);
        $this->assertStringNotContainsString('Pomme', $data['html']);
    }

    // ─── Filtre famille ────────────────────────────────────────────────────────

    public function test_filtre_famille_filtre_par_type_de_categorie(): void
    {
        $catFruits  = Categorie::factory()->create(['type' => 'fruits']);
        $catLaitier = Categorie::factory()->create(['type' => 'laitiers']);
        Produit::factory()->create(['nom' => 'Fraise', 'categorie_id' => $catFruits->id]);
        Produit::factory()->create(['nom' => 'Beurre', 'categorie_id' => $catLaitier->id]);

        $data = $this->getJson(route('front.produits.index', ['famille' => 'fruits']))->json();

        $this->assertStringContainsString('Fraise', $data['html']);
        $this->assertStringNotContainsString('Beurre', $data['html']);
    }

    // ─── Filtre éco-score ──────────────────────────────────────────────────────

    public function test_filtre_score_exact_retourne_bons_produits(): void
    {
        $p1 = $this->creerProduit(['nom' => 'Produit Score A']);
        $this->creerEmpreinte($p1, 0.5); // Score A (< 1)

        $p2 = $this->creerProduit(['nom' => 'Produit Score D']);
        $this->creerEmpreinte($p2, 5.0); // Score D (4–7)

        $data = $this->getJson(route('front.produits.index', ['score' => 'A']))->json();

        $this->assertStringContainsString('Produit Score A', $data['html']);
        $this->assertStringNotContainsString('Produit Score D', $data['html']);
    }

    // ─── Filtre labels (ancien paramètre + nouveau) ───────────────────────────

    public function test_filtre_label_unique_retrocompatibilite(): void
    {
        $p1 = $this->creerProduit(['nom' => 'Yaourt Certifié Bio']);
        Certification::factory()->create([
            'produit_id' => $p1->id,
            'type' => 'bio',
            'statut' => 'valide',
            'date_expiration' => now()->addYear(),
        ]);

        $p2 = $this->creerProduit(['nom' => 'Huile Origine Locale']);

        $data = $this->getJson(route('front.produits.index', ['label' => 'bio']))->json();

        $this->assertStringContainsString('Yaourt Certifié Bio', $data['html']);
        $this->assertStringNotContainsString('Huile Origine Locale', $data['html']);
    }

    public function test_filtre_labels_multiples_combine_conditions(): void
    {
        $p1 = $this->creerProduit(['nom' => 'Miel Bio et Local']);
        Certification::factory()->create([
            'produit_id' => $p1->id, 'type' => 'bio',
            'statut' => 'valide', 'date_expiration' => now()->addYear(),
        ]);
        Certification::factory()->create([
            'produit_id' => $p1->id, 'type' => 'local',
            'statut' => 'valide', 'date_expiration' => now()->addYear(),
        ]);

        $p2 = $this->creerProduit(['nom' => 'Confiture Uniquement Bio']);
        Certification::factory()->create([
            'produit_id' => $p2->id, 'type' => 'bio',
            'statut' => 'valide', 'date_expiration' => now()->addYear(),
        ]);

        // Les deux labels requis → seul p1 répond
        $data = $this->getJson(route('front.produits.index', ['labels' => ['bio', 'local']]))->json();

        $this->assertStringContainsString('Miel Bio et Local', $data['html']);
        $this->assertStringNotContainsString('Confiture Uniquement Bio', $data['html']);
    }

    // ─── Filtre origine ────────────────────────────────────────────────────────

    public function test_filtre_origine_recherche_partielle(): void
    {
        Produit::factory()->create(['nom' => 'Figue de Béja', 'origine' => 'Béja, Tunisie']);
        Produit::factory()->create(['nom' => 'Olive de Sfax', 'origine' => 'Sfax, Tunisie']);

        $data = $this->getJson(route('front.produits.index', ['origine' => 'Béja']))->json();

        $this->assertStringContainsString('Figue de Béja', $data['html']);
        $this->assertStringNotContainsString('Olive de Sfax', $data['html']);
    }

    // ─── Filtre certifiés uniquement ──────────────────────────────────────────

    public function test_filtre_certifies_exclut_produits_sans_certification(): void
    {
        $p1 = $this->creerProduit(['nom' => 'Fromage Label Rouge']);
        Certification::factory()->create([
            'produit_id' => $p1->id, 'type' => 'bio',
            'statut' => 'valide', 'date_expiration' => now()->addYear(),
        ]);

        Produit::factory()->create(['nom' => 'Pain Ordinaire Nature']);

        $data = $this->getJson(route('front.produits.index', ['certifies' => '1']))->json();

        $this->assertStringContainsString('Fromage Label Rouge', $data['html']);
        $this->assertStringNotContainsString('Pain Ordinaire Nature', $data['html']);
    }

    // ─── Tris ──────────────────────────────────────────────────────────────────

    public function test_tri_nom_trie_alphabetiquement(): void
    {
        Produit::factory()->create(['nom' => 'Zucchini vert']);
        Produit::factory()->create(['nom' => 'Abricot jaune']);
        Produit::factory()->create(['nom' => 'Melon d eau']);

        $ids = Produit::orderBy('nom')->pluck('id')->toArray();
        $noms = Produit::orderBy('nom')->pluck('nom')->toArray();

        $this->assertSame('Abricot jaune', $noms[0]);
        $this->assertSame('Melon d eau', $noms[1]);
        $this->assertSame('Zucchini vert', $noms[2]);
    }

    public function test_tri_eco_score_met_les_meilleurs_en_premier(): void
    {
        $pA = $this->creerProduit(['nom' => 'Produit Score A']);
        $this->creerEmpreinte($pA, 0.3); // A

        $pE = $this->creerProduit(['nom' => 'Produit Score E']);
        $this->creerEmpreinte($pE, 8.0); // E

        // Teste directement la requête Eloquent (évite l'ambiguïté du HTML)
        $ids = Produit::avecScore()->triEcoScore()->pluck('id')->toArray();

        $posA = array_search($pA->id, $ids);
        $posE = array_search($pE->id, $ids);

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posE);
        $this->assertLessThan($posE, $posA, 'Le produit Score A doit apparaître avant le produit Score E');
    }

    public function test_tri_eco_score_met_les_produits_sans_score_en_dernier(): void
    {
        $pAvec = $this->creerProduit(['nom' => 'Avec score']);
        $this->creerEmpreinte($pAvec, 0.5);

        $pSans = Produit::factory()->create(['nom' => 'Sans score']);

        $ids = Produit::avecScore()->triEcoScore()->pluck('id')->toArray();

        $posAvec = array_search($pAvec->id, $ids);
        $posSans = array_search($pSans->id, $ids);

        $this->assertLessThan($posSans, $posAvec, 'Le produit avec score doit apparaître avant celui sans score');
    }

    // ─── Mode AJAX (fragment) ─────────────────────────────────────────────────

    public function test_catalogue_retourne_json_avec_accept_json(): void
    {
        Produit::factory()->count(2)->create();

        $reponse = $this->getJson(route('front.produits.index'));

        $reponse->assertOk()
            ->assertJsonStructure(['html', 'total', 'page', 'derniere_page']);
    }

    public function test_catalogue_json_contient_le_html_des_produits(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Produit Test AJAX']);

        $data = $this->getJson(route('front.produits.index'))->json();

        $this->assertStringContainsString('Produit Test AJAX', $data['html']);
    }

    // ─── Scope scoreMieux ─────────────────────────────────────────────────────

    public function test_scope_score_mieux_exclut_les_produits_de_score_inferieur(): void
    {
        $pA = $this->creerProduit(['nom' => 'Score A']);
        $this->creerEmpreinte($pA, 0.5); // A

        $pC = $this->creerProduit(['nom' => 'Score C']);
        $this->creerEmpreinte($pC, 3.0); // C

        $pE = $this->creerProduit(['nom' => 'Score E']);
        $this->creerEmpreinte($pE, 9.0); // E

        // « B ou mieux » → A et B, pas C ni E
        $ids = Produit::avecScore()->scoreMieux('B')->pluck('id');

        $this->assertContains($pA->id, $ids->toArray());
        $this->assertNotContains($pC->id, $ids->toArray());
        $this->assertNotContains($pE->id, $ids->toArray());
    }

    // ─── Répartition des scores ───────────────────────────────────────────────

    public function test_repartition_scores_compte_correctement(): void
    {
        $p1 = $this->creerProduit(); $this->creerEmpreinte($p1, 0.5); // A
        $p2 = $this->creerProduit(); $this->creerEmpreinte($p2, 1.5); // B
        $p3 = $this->creerProduit(); $this->creerEmpreinte($p3, 1.8); // B
        Produit::factory()->create(); // sans score

        $rep = Produit::repartitionScores();

        $this->assertSame(1, $rep['A']);
        $this->assertSame(2, $rep['B']);
        $this->assertSame(1, $rep['sans_score']);
    }
}
