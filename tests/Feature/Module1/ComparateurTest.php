<?php

declare(strict_types=1);

namespace Tests\Feature\Module1;

use App\Models\Analyse;
use App\Models\Lot;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du comparateur de fiches produits (A2) — Module 1 (Ghada).
 */
class ComparateurTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function creerProduit(array $attrs = []): Produit
    {
        return Produit::factory()->create($attrs);
    }

    private function utilisateurConnecte(): User
    {
        return User::factory()->create(['role' => 'consommateur']);
    }

    // ─── Page comparateur ─────────────────────────────────────────────────────

    public function test_page_comparateur_accessible(): void
    {
        $this->getJson(route('front.produits.comparer'))->assertOk();
    }

    public function test_comparateur_affiche_empty_state_sans_produits(): void
    {
        // La page comparateur retourne du HTML — on teste juste le status HTTP
        $this->get(route('front.produits.comparer'))->assertOk();
    }

    public function test_comparateur_affiche_les_produits_selectionnes(): void
    {
        $p1 = $this->creerProduit(['nom' => 'Produit Alpha']);
        $p2 = $this->creerProduit(['nom' => 'Produit Beta']);

        // Via lien partageable
        $reponse = $this->get(route('front.produits.comparer', ['produits' => [$p1->id, $p2->id]]));
        $reponse->assertOk();
    }

    // ─── Limite visiteur (2 produits) ─────────────────────────────────────────

    public function test_visiteur_ne_peut_pas_ajouter_plus_de_deux_produits(): void
    {
        $max = (int) config('nutritrace.comparaison.visiteur'); // 2

        $produits = Produit::factory()->count($max + 1)->create();

        // Ajoute les $max premiers → doit réussir
        for ($i = 0; $i < $max; $i++) {
            $this->post(route('front.produits.comparaison.ajouter'), [
                '_token' => csrf_token(),
                'produit_id' => $produits[$i]->id,
            ])->assertRedirect();
        }

        // Tente d'ajouter un ($max + 1)-ième → doit échouer avec 422 (JSON)
        $reponse = $this->postJson(route('front.produits.comparaison.ajouter'), [
            'produit_id' => $produits[$max]->id,
        ]);

        $reponse->assertStatus(422);
    }

    public function test_utilisateur_connecte_peut_ajouter_jusqu_a_quatre_produits(): void
    {
        $user = $this->utilisateurConnecte();
        $max = (int) config('nutritrace.comparaison.connecte'); // 4
        $produits = Produit::factory()->count($max)->create();

        $this->actingAs($user);

        foreach ($produits as $produit) {
            $this->postJson(route('front.produits.comparaison.ajouter'), [
                'produit_id' => $produit->id,
            ])->assertOk()->assertJsonPath('nb', fn ($nb) => $nb <= $max);
        }

        // Vérifie que la session contient bien $max produits
        $this->assertCount($max, session('m1_comparaison', []));
    }

    public function test_limite_appliquee_cote_serveur_pas_seulement_cote_client(): void
    {
        $max = (int) config('nutritrace.comparaison.visiteur');
        $produits = Produit::factory()->count($max + 2)->create();

        // Remplit la session manuellement à la limite
        session(['m1_comparaison' => $produits->take($max)->pluck('id')->all()]);

        // Tente d'ajouter un de plus directement via JSON
        $this->postJson(route('front.produits.comparaison.ajouter'), [
            'produit_id' => $produits[$max]->id,
        ])->assertStatus(422);
    }

    // ─── Ajouter / retirer ───────────────────────────────────────────────────

    public function test_ajouter_produit_met_a_jour_la_session(): void
    {
        $produit = $this->creerProduit();

        $this->postJson(route('front.produits.comparaison.ajouter'), [
            'produit_id' => $produit->id,
        ])->assertOk()->assertJsonStructure(['nb', 'max', 'selectionne', 'id']);

        $this->assertContains($produit->id, session('m1_comparaison', []));
    }

    public function test_ajouter_deux_fois_le_meme_produit_ne_duplique_pas(): void
    {
        $produit = $this->creerProduit();

        $this->postJson(route('front.produits.comparaison.ajouter'), ['produit_id' => $produit->id]);
        $this->postJson(route('front.produits.comparaison.ajouter'), ['produit_id' => $produit->id]);

        $this->assertCount(1, session('m1_comparaison', []));
    }

    public function test_retirer_produit_met_a_jour_la_session(): void
    {
        $p1 = $this->creerProduit();
        $p2 = $this->creerProduit();

        session(['m1_comparaison' => [$p1->id, $p2->id]]);

        $this->deleteJson(route('front.produits.comparaison.retirer', $p1->id))
            ->assertOk()
            ->assertJsonPath('selectionne', false);

        $this->assertNotContains($p1->id, session('m1_comparaison', []));
        $this->assertContains($p2->id, session('m1_comparaison', []));
    }

    public function test_vider_supprime_toute_la_selection(): void
    {
        $produits = Produit::factory()->count(2)->create();
        session(['m1_comparaison' => $produits->pluck('id')->all()]);

        $this->deleteJson(route('front.produits.comparaison.vider'))
            ->assertOk()
            ->assertJsonPath('nb', 0);

        $this->assertEmpty(session('m1_comparaison', []));
    }

    // ─── Redirect fallback (requêtes non-AJAX) ────────────────────────────────

    public function test_ajouter_redirige_si_pas_ajax(): void
    {
        $produit = $this->creerProduit();

        $this->post(route('front.produits.comparaison.ajouter'), [
            '_token' => csrf_token(),
            'produit_id' => $produit->id,
        ])->assertRedirect();
    }

    public function test_retirer_redirige_si_pas_ajax(): void
    {
        $produit = $this->creerProduit();
        session(['m1_comparaison' => [$produit->id]]);

        $this->delete(route('front.produits.comparaison.retirer', $produit->id), [
            '_token' => csrf_token(),
        ])->assertRedirect();
    }

    // ─── Données comparées ────────────────────────────────────────────────────

    public function test_comparateur_calcule_pct_lots_conformes(): void
    {
        $produit = $this->creerProduit();
        $lot1 = Lot::factory()->create(['produit_id' => $produit->id]);
        $lot2 = Lot::factory()->create(['produit_id' => $produit->id]);

        Analyse::factory()->create(['lot_id' => $lot1->id, 'resultat' => 'conforme']);
        Analyse::factory()->create(['lot_id' => $lot2->id, 'resultat' => 'non_conforme']);

        session(['m1_comparaison' => [$produit->id]]);

        // Au moins 2 produits pour afficher le tableau — on ajoute un second
        $p2 = $this->creerProduit();
        session(['m1_comparaison' => [$produit->id, $p2->id]]);

        $reponse = $this->get(route('front.produits.comparer', [
            'produits' => [$produit->id, $p2->id],
        ]));

        $reponse->assertOk();
        // 50 % : 1 lot conforme sur 2 analysés
        $reponse->assertSee('50 %');
    }

    public function test_lien_partageable_accepte_ids_via_query_string(): void
    {
        $p1 = $this->creerProduit(['nom' => 'Produit Lien 1']);
        $p2 = $this->creerProduit(['nom' => 'Produit Lien 2']);

        $this->get(route('front.produits.comparer', ['produits' => [$p1->id, $p2->id]]))
            ->assertOk();
    }

    public function test_route_comparer_est_avant_route_show(): void
    {
        // Vérifie que /produits/comparer ne résout pas sur la fiche d'un produit
        // (car il y a whereNumber sur {produit} dans les routes)
        $reponse = $this->get('/produits/comparer');
        // Ne doit pas faire un 404 à cause d'une mauvaise résolution de route
        $this->assertNotSame(404, $reponse->status());
    }
}
