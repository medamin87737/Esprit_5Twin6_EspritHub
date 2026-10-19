<?php

namespace Tests\Feature;

use App\Models\Analyse;
use App\Models\Laboratoire;
use App\Models\Lot;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 2 (Amin) — popup Qualité des cartes produits (sans quitter la page).
 */
class Module2QualitePopupTest extends TestCase
{
    use RefreshDatabase;

    private User $consommateur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->consommateur = User::factory()->create([
            'role' => 'consommateur',
            'active' => true,
        ]);
    }

    public function test_popup_qualite_produit_affiche_synthese_et_analyses(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Jus d orange popup']);
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);
        $labo = Laboratoire::factory()->create(['nom' => 'Labo Popup']);
        Analyse::factory()->create([
            'lot_id' => $lot->id,
            'laboratoire_id' => $labo->id,
            'resultat' => 'conforme',
        ]);
        Analyse::factory()->create([
            'lot_id' => $lot->id,
            'laboratoire_id' => $labo->id,
            'resultat' => 'en_attente',
        ]);

        $response = $this->actingAs($this->consommateur)
            ->get(route('consommateur.qualite.produit', $produit));

        $response->assertOk();
        $response->assertSee('Jus d orange popup');
        $response->assertSee('Labo Popup');
        $response->assertSee('Dernières analyses');
        // Assistant intelligent intégré au popup
        $response->assertSee('Assistant NutriTrace');
        $response->assertSee('data-assistant-form', false);
        $response->assertSee('data-qualite-url', false);
    }

    public function test_catalogue_utilise_bouton_popup_pas_lien_qualite(): void
    {
        Produit::factory()->create(['nom' => 'Produit catalogue popup']);

        $response = $this->actingAs($this->consommateur)->get(route('front.produits.index'));

        $response->assertOk();
        $response->assertSee('data-nt-qualite-popup', false);
        $response->assertSee('ntQualiteModal', false);
    }

    public function test_popup_lien_qualite_complete_ouvre_ecran_qualite_du_lot(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Miel complet popup']);
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);
        $labo = Laboratoire::factory()->create(['nom' => 'Labo Complet']);
        Analyse::factory()->create([
            'lot_id' => $lot->id,
            'laboratoire_id' => $labo->id,
            'resultat' => 'conforme',
        ]);

        // Le popup pointe vers l'écran Qualité existant, lot déjà chargé
        $this->actingAs($this->consommateur)
            ->get(route('consommateur.qualite.produit', $produit))
            ->assertOk()
            ->assertSee(route('consommateur.qualite.index', ['numero' => $lot->numero_lot]), false);

        // Et cet écran affiche bien le lot du produit
        $this->actingAs($this->consommateur)
            ->get(route('consommateur.qualite.index', ['numero' => $lot->numero_lot]))
            ->assertOk()
            ->assertSee('Miel complet popup')
            ->assertSee('Labo Complet');
    }

    public function test_popup_qualite_refuse_aux_invites(): void
    {
        $produit = Produit::factory()->create();

        $this->get(route('consommateur.qualite.produit', $produit))
            ->assertRedirect(route('login'));
    }
}
