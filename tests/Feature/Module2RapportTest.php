<?php

namespace Tests\Feature;

use App\Models\Analyse;
use App\Models\Lot;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 2 (Amin) — bouton « Télécharger le rapport » toujours disponible.
 */
class Module2RapportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_telechargement_rapport_genere_sans_fichier_joint(): void
    {
        $consommateur = User::factory()->create(['role' => 'consommateur', 'active' => true]);
        $produit = Produit::factory()->create(['nom' => 'Jus rapport test']);
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);
        $analyse = Analyse::factory()->create([
            'lot_id' => $lot->id,
            'resultat' => 'conforme',
            'rapport' => null,
        ]);

        $response = $this->actingAs($consommateur)
            ->get(route('front.analyses.rapport', $analyse));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="Rapport-'.$analyse->numero.'.html"');
        $this->assertStringContainsString($analyse->numero, $response->getContent());
        $this->assertStringContainsString('Jus rapport test', $response->getContent());
    }

    public function test_carte_analyse_affiche_bouton_telecharger_rapport(): void
    {
        $consommateur = User::factory()->create(['role' => 'consommateur', 'active' => true]);
        $produit = Produit::factory()->create(['nom' => 'Produit carte rapport']);
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);
        Analyse::factory()->create(['lot_id' => $lot->id, 'resultat' => 'conforme', 'rapport' => null]);

        $this->actingAs($consommateur)
            ->get(route('consommateur.qualite.index', ['numero' => $lot->numero_lot]))
            ->assertOk()
            ->assertSee('Télécharger le rapport');
    }
}
