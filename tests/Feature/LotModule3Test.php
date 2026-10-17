<?php

namespace Tests\Feature;

use App\Models\Acteur;
use App\Models\Categorie;
use App\Models\Etape;
use App\Models\Lot;
use App\Models\Produit;
use App\Models\TypeActeur;
use App\Models\User;
use App\Support\Periode;
use App\Support\TableauDeBord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotModule3Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $consommateur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $this->consommateur = User::factory()->create([
            'role' => 'consommateur',
            'active' => true,
        ]);
    }

    public function test_admin_peut_exporter_les_lots_au_format_csv_avec_etapes(): void
    {
        $categorie = Categorie::factory()->create(['nom' => 'Huiles', 'type' => 'autres']);
        $produit = Produit::factory()->create([
            'categorie_id' => $categorie->id,
            'nom' => 'Huile d\'olive Extra',
        ]);

        $typeActeur = TypeActeur::factory()->create(['libelle' => 'Producteur', 'role_chaine' => 'producteur']);
        $acteur = Acteur::factory()->create([
            'type_acteur_id' => $typeActeur->id,
            'nom' => 'Ferme Test',
            'adresse' => 'Zaghouan',
        ]);

        $lot = Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-TEST-001',
            'quantite' => 500,
            'date_production' => today()->subDays(5),
            'date_peremption' => today()->addDays(20),
        ]);

        Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'type_etape' => 'production',
            'date_heure' => today()->subDays(5)->setTime(8, 0),
            'lieu' => 'Oliveraie Zaghouan',
            'mode_transport' => 'aucun',
            'remarques' => 'Récolte manuelle',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.lots.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('lots_nutritrace_', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();

        // Vérification présence BOM UTF-8
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Vérification en-têtes
        $this->assertStringContainsString('"Numéro de lot"', $content);
        $this->assertStringContainsString('Produit;Catégorie', $content);
        $this->assertStringContainsString('Parcours complet', $content);

        // Vérification contenu du lot et de son étape
        $this->assertStringContainsString('LOT-TEST-001', $content);
        $this->assertStringContainsString('Huile d\'olive Extra', $content);
        $this->assertStringContainsString('Ferme Test', $content);
        $this->assertStringContainsString('Oliveraie Zaghouan', $content);
        $this->assertStringContainsString('Récolte manuelle', $content);

        // Exactement 1 ligne de données pour ce lot (aucun doublon) + 1 ligne d'en-tête
        $lignes = array_filter(explode("\n", trim($content)));
        $this->assertCount(2, $lignes);
    }

    public function test_export_respecte_les_filtres_actifs(): void
    {
        $produitA = Produit::factory()->create(['nom' => 'Lait A']);
        $produitB = Produit::factory()->create(['nom' => 'Fromage B']);

        Lot::factory()->create([
            'produit_id' => $produitA->id,
            'numero_lot' => 'LOT-LAIT-999',
            'quantite' => 100,
            'date_production' => today(),
            'date_peremption' => today()->addDays(10),
        ]);

        Lot::factory()->create([
            'produit_id' => $produitB->id,
            'numero_lot' => 'LOT-FROMAGE-888',
            'quantite' => 200,
            'date_production' => today(),
            'date_peremption' => today()->addDays(10),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.lots.export', ['q' => 'LAIT']));
        $content = $response->streamedContent();

        $this->assertStringContainsString('LOT-LAIT-999', $content);
        $this->assertStringNotContainsString('LOT-FROMAGE-888', $content);

        // Exactement 1 seul produit dans l'export (1 ligne d'en-tête + 1 ligne pour le lot = 2 lignes)
        $lignes = array_filter(explode("\n", trim($content)));
        $this->assertCount(2, $lignes);
    }

    public function test_utilisateur_non_admin_ne_peut_pas_exporter(): void
    {
        $response = $this->actingAs($this->consommateur)->get(route('admin.lots.export'));
        $response->assertForbidden();

        $guestResponse = $this->get(route('admin.lots.export'));
        $guestResponse->assertForbidden();
    }

    public function test_calcul_alerte_et_statuts_peremption(): void
    {
        $produit = Produit::factory()->create();

        $lotExpire = Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-EXPIRE',
            'date_production' => today()->subDays(40),
            'date_peremption' => today()->subDays(5),
        ]);

        $lotJ7 = Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-J7',
            'date_production' => today()->subDays(10),
            'date_peremption' => today()->addDays(4),
        ]);

        $lotJ30 = Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-J30',
            'date_production' => today()->subDays(10),
            'date_peremption' => today()->addDays(18),
        ]);

        $lotValide = Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-VALIDE',
            'date_production' => today(),
            'date_peremption' => today()->addDays(120),
        ]);

        $this->assertSame('expire', $lotExpire->alertePeremption());
        $this->assertSame(-5, $lotExpire->joursAvantPeremption());

        $this->assertSame('j7', $lotJ7->alertePeremption());
        $this->assertSame(4, $lotJ7->joursAvantPeremption());

        $this->assertSame('j30', $lotJ30->alertePeremption());
        $this->assertSame(18, $lotJ30->joursAvantPeremption());

        $this->assertSame('valide', $lotValide->alertePeremption());
        $this->assertSame(120, $lotValide->joursAvantPeremption());

        // Test scopePeremption
        $this->assertTrue(Lot::peremption('j7')->where('id', $lotJ7->id)->exists());
        $this->assertFalse(Lot::peremption('j7')->where('id', $lotJ30->id)->exists());

        $this->assertTrue(Lot::peremption('j30')->where('id', $lotJ30->id)->exists());
        $this->assertTrue(Lot::peremption('expire')->where('id', $lotExpire->id)->exists());
    }

    public function test_dashboard_genere_alertes_j7_et_j30(): void
    {
        $produit = Produit::factory()->create();

        Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-ALERTE-J7',
            'quantite' => 50,
            'date_production' => today()->subDays(10),
            'date_peremption' => today()->addDays(3),
        ]);

        Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-ALERTE-J30',
            'quantite' => 50,
            'date_production' => today()->subDays(10),
            'date_peremption' => today()->addDays(22),
        ]);

        $tableauDeBord = new TableauDeBord(Periode::depuisRequete(new \Illuminate\Http\Request()));
        $alertes = $tableauDeBord->alertes();

        $titresAlertes = array_column($alertes, 'titre');

        $this->assertTrue(collect($titresAlertes)->contains(fn ($t) => str_contains($t, 'J-7')));
        $this->assertTrue(collect($titresAlertes)->contains(fn ($t) => str_contains($t, 'J-30')));

        // Vérification de la présence des alertes sur la vue du tableau de bord admin
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('J-7');
        $response->assertSee('J-30');
    }

    public function test_vue_index_affiche_filtre_et_badges_peremption(): void
    {
        $produit = Produit::factory()->create();

        Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-VIEW-J7',
            'quantite' => 10,
            'date_production' => today(),
            'date_peremption' => today()->addDays(5),
        ]);

        Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-VIEW-J30',
            'quantite' => 10,
            'date_production' => today(),
            'date_peremption' => today()->addDays(20),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.lots.index'));
        $response->assertOk();
        $response->assertSee('Exporter CSV / Excel');
        $response->assertSee('J-5');
        $response->assertSee('J-20');

        // Test filtre j7
        $responseJ7 = $this->actingAs($this->admin)->get(route('admin.lots.index', ['peremption' => 'j7']));
        $responseJ7->assertOk();
        $responseJ7->assertSee('LOT-VIEW-J7');
        $responseJ7->assertDontSee('LOT-VIEW-J30');
    }
}
