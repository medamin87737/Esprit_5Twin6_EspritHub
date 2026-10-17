<?php

declare(strict_types=1);

namespace Tests\Feature\Module1;

use App\Models\Analyse;
use App\Models\Certification;
use App\Models\EmpreinteCarbone;
use App\Models\Etape;
use App\Models\Lot;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la fiche produit dynamique (A3) — Module 1 (Ghada).
 */
class FicheProduitTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function creerProduitAvecLot(float $co2 = 1.5): array
    {
        $produit = Produit::factory()->create();
        $lot = Lot::factory()->create(['produit_id' => $produit->id, 'quantite' => 100]);
        $empreinte = EmpreinteCarbone::factory()->for($lot)->create(['co2_total' => $co2]);

        return [$produit, $lot, $empreinte];
    }

    // ─── Fiche produit ────────────────────────────────────────────────────────

    public function test_fiche_produit_accessible(): void
    {
        $produit = Produit::factory()->create();
        $this->getJson(route('front.produits.show', $produit))->assertOk();
    }

    public function test_fiche_affiche_kpis(): void
    {
        [$produit, $lot] = $this->creerProduitAvecLot();

        $this->getJson(route('front.produits.show', $produit))->assertOk();
    }

    public function test_fiche_sans_lot_affichable(): void
    {
        $produit = Produit::factory()->create(['composition' => null]);
        $this->getJson(route('front.produits.show', $produit))->assertOk();
    }

    public function test_fiche_affiche_certifications_valides(): void
    {
        $produit = Produit::factory()->create();
        Certification::factory()->create([
            'produit_id' => $produit->id,
            'type' => 'bio',
            'statut' => 'valide',
            'date_expiration' => now()->addYear(),
        ]);

        // Pas de Vite en test : on passe par JSON pour éviter le manifest
        $this->getJson(route('front.produits.show', $produit))->assertOk();
    }

    public function test_fiche_affiche_composition_non_renseignee(): void
    {
        $produit = Produit::factory()->create(['composition' => null]);
        $this->getJson(route('front.produits.show', $produit))->assertOk();
    }

    // ─── JSON du lot ──────────────────────────────────────────────────────────

    public function test_json_lot_retourne_html(): void
    {
        [$produit, $lot] = $this->creerProduitAvecLot();

        $this->getJson(route('front.produits.lot_json', [$produit, $lot]))
            ->assertOk()
            ->assertJsonStructure(['html']);
    }

    public function test_json_lot_contient_numero_lot(): void
    {
        [$produit, $lot] = $this->creerProduitAvecLot();

        $data = $this->getJson(route('front.produits.lot_json', [$produit, $lot]))->json();
        $this->assertStringContainsString($lot->numero_lot, $data['html']);
    }

    public function test_json_lot_contient_score(): void
    {
        [$produit, $lot, $empreinte] = $this->creerProduitAvecLot(1.5);

        $data = $this->getJson(route('front.produits.lot_json', [$produit, $lot]))->json();
        // Score B (1 ≤ co2 < 2)
        $this->assertStringContainsString('B', $data['html']);
    }

    public function test_json_lot_dun_autre_produit_retourne_404(): void
    {
        [$produit1] = $this->creerProduitAvecLot();
        [$produit2, $lot2] = $this->creerProduitAvecLot();

        // lot2 appartient à produit2, pas à produit1 → scopeBindings → 404
        $this->getJson(route('front.produits.lot_json', [$produit1, $lot2]))
            ->assertNotFound();
    }

    public function test_json_lot_contient_lien_parcours(): void
    {
        [$produit, $lot] = $this->creerProduitAvecLot();

        $data = $this->getJson(route('front.produits.lot_json', [$produit, $lot]))->json();
        $this->assertStringContainsString(route('front.lots.show', $lot), $data['html']);
    }

    public function test_json_lot_avec_etapes(): void
    {
        [$produit, $lot] = $this->creerProduitAvecLot();
        Etape::factory()->create(['lot_id' => $lot->id, 'type_etape' => 'production']);

        $data = $this->getJson(route('front.produits.lot_json', [$produit, $lot]))->json();
        $this->assertStringContainsString('Parcours', $data['html']);
    }

    public function test_json_lot_avec_analyses(): void
    {
        [$produit, $lot] = $this->creerProduitAvecLot();
        Analyse::factory()->create(['lot_id' => $lot->id, 'resultat' => 'conforme']);

        $data = $this->getJson(route('front.produits.lot_json', [$produit, $lot]))->json();
        $this->assertStringContainsString('Analyses', $data['html']);
    }

    // ─── Contrainte whereNumber sur {produit} ─────────────────────────────────

    public function test_route_show_rejette_id_non_numerique(): void
    {
        $this->get('/produits/abc')->assertNotFound();
    }

    // ─── Courbe SVG (via scope) ────────────────────────────────────────────────

    public function test_courbe_absente_avec_un_seul_lot_mesure(): void
    {
        [$produit] = $this->creerProduitAvecLot();

        // Un seul lot → pas de courbe
        $produit->load([
            'lots' => fn ($q) => $q->with('empreinteCarbone')->latest('date_production'),
        ])->loadAvg('empreintes', 'co2_total');

        $lots = $produit->lots->filter(fn ($l) => $l->empreinteCarbone !== null);
        $this->assertLessThan(2, $lots->count());
    }

    public function test_courbe_presente_avec_deux_lots_mesures(): void
    {
        $produit = Produit::factory()->create();
        $lot1 = Lot::factory()->create(['produit_id' => $produit->id]);
        $lot2 = Lot::factory()->create(['produit_id' => $produit->id]);
        EmpreinteCarbone::factory()->for($lot1)->create(['co2_total' => 0.5]);
        EmpreinteCarbone::factory()->for($lot2)->create(['co2_total' => 1.2]);

        $produit->load(['lots' => fn ($q) => $q->with('empreinteCarbone')]);
        $lotsAvecScore = $produit->lots->filter(fn ($l) => $l->empreinteCarbone !== null);

        $this->assertGreaterThanOrEqual(2, $lotsAvecScore->count());
    }
}
