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

    public function test_page_tracabilite_publique_et_recherche_de_lot(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Dattes Deglet Nour']);
        $lot = Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-DATTES-001',
        ]);

        // 1. Accès à la page de recherche publique
        $response = $this->get(route('front.lots.search'));
        $response->assertOk();
        $response->assertSee('Tracer un lot');

        // 2. Recherche avec numéro existant : redirection vers la fiche du lot
        $searchResponse = $this->get(route('front.lots.search', ['numero' => 'LOT-DATTES-001']));
        $searchResponse->assertRedirect(route('front.lots.show', $lot));
    }

    public function test_fiche_lot_affiche_timeline_parcours_et_carte_leaflet(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Huile d\'olive Extra Vierge']);
        $lot = Lot::factory()->create([
            'produit_id' => $produit->id,
            'numero_lot' => 'LOT-HUILE-777',
        ]);

        $typeActeur = TypeActeur::factory()->create(['libelle' => 'Producteur', 'role_chaine' => 'producteur']);
        $acteur1 = Acteur::factory()->create([
            'type_acteur_id' => $typeActeur->id,
            'nom' => 'Oliveraie El Baraka',
            'latitude' => 36.4,
            'longitude' => 10.1,
            'adresse' => 'Zaghouan',
        ]);

        $acteur2 = Acteur::factory()->create([
            'type_acteur_id' => $typeActeur->id,
            'nom' => 'Moulin du Nord',
            'latitude' => 36.8,
            'longitude' => 10.2,
            'adresse' => 'Tunis',
        ]);

        Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur1->id,
            'type_etape' => 'production',
            'lieu' => 'Zaghouan',
            'date_heure' => today()->subDays(3)->setTime(7, 30),
        ]);

        Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur2->id,
            'type_etape' => 'transformation',
            'lieu' => 'Tunis',
            'date_heure' => today()->subDays(2)->setTime(10, 0),
        ]);

        $response = $this->get(route('front.lots.show', $lot));
        $response->assertOk();

        // Vérification de la Timeline complète
        $response->assertSee('Les étapes du parcours');
        $response->assertSee('Oliveraie El Baraka');
        $response->assertSee('Moulin du Nord');
        $response->assertSee('Zaghouan');
        $response->assertSee('Tunis');

        // Vérification de la Carte Leaflet GPS avec les coordonnées
        $response->assertSee('Sur la carte');
        $response->assertSee('map-canvas');
        $response->assertSee('data-carte');
        $response->assertSee('36.4');
        $response->assertSee('10.1');
        $response->assertSee('data-trace="1"', false);
    }

    public function test_admin_peut_acceder_au_formulaire_etape_avec_selecteur_carte_leaflet(): void
    {
        $produit = Produit::factory()->create();
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);
        $typeActeur = TypeActeur::factory()->create();
        Acteur::factory()->create([
            'type_acteur_id' => $typeActeur->id,
            'nom' => 'Station Sousse',
            'latitude' => 35.8256,
            'longitude' => 10.6369,
            'adresse' => 'Zone Portuaire, Sousse',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.etapes.create', ['lot' => $lot->id]));

        $response->assertOk();
        // Vérification des éléments du sélecteur de carte Leaflet
        $response->assertSee('etape-map-picker');
        $response->assertSee('map-search-input');
        $response->assertSee('btn-map-search');
        $response->assertSee('map-picker-status');
        $response->assertSee('leaflet.js');
        $response->assertSee('leaflet.css');
        $response->assertSee('Station Sousse');
    }

    public function test_admin_peut_creer_une_etape_avec_lieu_geolocalise(): void
    {
        $produit = Produit::factory()->create();
        $lot = Lot::factory()->create([
            'produit_id' => $produit->id,
            'date_production' => today()->subDays(10),
        ]);
        $typeActeur = TypeActeur::factory()->create();
        $acteur = Acteur::factory()->create(['type_acteur_id' => $typeActeur->id]);

        $postData = [
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'type_etape' => 'transformation',
            'date_heure' => today()->subDays(5)->format('Y-m-d\TH:i'),
            'lieu' => 'Avenue Habib Bourguiba, Tunis, Tunisie',
            'latitude' => 36.80000000,
            'longitude' => 10.18000000,
            'mode_transport' => 'camion_frigorifique',
            'remarques' => 'Conditionnement sous température dirigée.',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.etapes.store'), $postData);

        $response->assertRedirect(route('admin.lots.show', $lot));
        $this->assertDatabaseHas('etapes', [
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'lieu' => 'Avenue Habib Bourguiba, Tunis, Tunisie',
            'latitude' => 36.80000000,
            'longitude' => 10.18000000,
            'mode_transport' => 'camion_frigorifique',
        ]);
    }

    public function test_carte_publique_utilise_coordonnees_propres_de_letape(): void
    {
        $produit = Produit::factory()->create();
        $lot = Lot::factory()->create(['produit_id' => $produit->id, 'numero_lot' => 'LOT-GPS-PROPRE']);

        $typeActeur = TypeActeur::factory()->create();
        // Acteur avec des coordonnées à Sfax
        $acteur = Acteur::factory()->create([
            'type_acteur_id' => $typeActeur->id,
            'nom' => 'Société Centrale',
            'latitude' => 34.7400,
            'longitude' => 10.7600,
        ]);

        // Étape avec son vrai lieu physique à Bizerte (coordonnées propres)
        Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'type_etape' => 'production',
            'lieu' => 'Champ de Bizerte',
            'latitude' => 37.2744,
            'longitude' => 9.8739,
            'date_heure' => today()->subDays(2),
        ]);

        $response = $this->get(route('front.lots.show', $lot));

        $response->assertOk();
        // La carte publique doit afficher les coordonnées réelles de l'étape (Bizerte: 37.2744, 9.8739)
        $response->assertSee('37.2744');
        $response->assertSee('9.8739');
        $response->assertSee('Champ de Bizerte');
    }

    public function test_recit_ia_histoire_du_produit_genere_et_mis_en_cache(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Dattes Deglet Nour']);
        $lot = Lot::factory()->create(['produit_id' => $produit->id, 'recit_ia' => null]);
        $typeActeur = TypeActeur::factory()->create();
        $acteur = Acteur::factory()->create(['type_acteur_id' => $typeActeur->id, 'nom' => 'Oasis Tozeur']);

        $etape = Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'type_etape' => 'production',
            'lieu' => 'Tozeur',
            'date_heure' => today()->subDays(3),
        ]);

        $this->assertNull($lot->recit_ia);

        // 1. Accès à la page publique : l'histoire est générée et mise en cache
        $response = $this->get(route('front.lots.show', $lot));
        $response->assertOk();
        $response->assertSee('Histoire du produit');
        $response->assertSee('Dattes Deglet Nour');

        $lotFresh = $lot->fresh();
        $this->assertNotNull($lotFresh->recit_ia);
        $this->assertStringContainsString('Dattes Deglet Nour', $lotFresh->recit_ia);

        // 2. Invalidation du cache quand une étape est modifiée
        $etape->update(['lieu' => 'Nefta']);
        $this->assertNull($lot->fresh()->recit_ia);
    }

    public function test_assistant_ai_etape_analyse_phrase_langage_naturel(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.etapes.assistant-ai'), [
            'prompt' => 'Livré à Tunis hier à 14h par camion frigorifique',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'type_etape' => 'distribution',
                'lieu' => 'Tunis',
                'mode_transport' => 'camion_frigorifique',
            ],
        ]);
        $this->assertStringContainsString('T14:00', $response->json('data.date_heure'));

        // Test détection du verbe "produit" -> type production
        $responseProd = $this->actingAs($this->admin)->postJson(route('admin.etapes.assistant-ai'), [
            'prompt' => 'produit à Zaghouan le 12/10/2026',
        ]);
        $responseProd->assertOk();
        $this->assertSame('production', $responseProd->json('data.type_etape'));
        $this->assertSame('Zaghouan', $responseProd->json('data.lieu'));

        // Test avec date en lettres "19 octobre 2026 à 14h30 à tunis ariana" -> transformation
        $responseTrans = $this->actingAs($this->admin)->postJson(route('admin.etapes.assistant-ai'), [
            'prompt' => 'transformé le 19 octobre 2026 à 14h30 à tunis ariana',
        ]);
        $responseTrans->assertOk();
        $this->assertSame('transformation', $responseTrans->json('data.type_etape'));
        $this->assertStringContainsString('2026-10-19T14:30', $responseTrans->json('data.date_heure'));
        $this->assertStringContainsString('tunis ariana', strtolower($responseTrans->json('data.lieu')));
    }

    public function test_admin_peut_regenerer_manuellement_recit_ia(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Huile d\'olive']);
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);
        $typeActeur = TypeActeur::factory()->create();
        $acteur = Acteur::factory()->create(['type_acteur_id' => $typeActeur->id]);

        Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'type_etape' => 'production',
            'lieu' => 'Zaghouan',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.lots.recit-ia', $lot));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertNotNull($lot->fresh()->recit_ia);
    }

    public function test_audit_ia_detecte_coherence_et_anomalies_du_parcours(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Lait Frais']);
        $lot = Lot::factory()->create([
            'produit_id' => $produit->id,
            'date_production' => today()->subDays(3),
            'date_peremption' => today()->addDays(5),
        ]);
        $typeActeur = TypeActeur::factory()->create();
        $acteur = Acteur::factory()->create(['type_acteur_id' => $typeActeur->id]);

        // Parcours cohérent
        Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'type_etape' => 'production',
            'lieu' => 'Béja',
            'date_heure' => today()->subDays(2)->setTime(8, 0),
            'latitude' => 36.7256,
            'longitude' => 9.1817,
            'mode_transport' => 'aucun',
        ]);
        Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'type_etape' => 'distribution',
            'lieu' => 'Tunis',
            'date_heure' => today()->subDays(1)->setTime(14, 0),
            'latitude' => 36.8065,
            'longitude' => 10.1815,
            'mode_transport' => 'camion_frigorifique',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.lots.audit-ia', $lot));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lotFresh = $lot->fresh();
        $this->assertNotNull($lotFresh->audit_ia);
        $this->assertGreaterThanOrEqual(80, $lotFresh->audit_ia['score']);
        $this->assertSame('conforme', $lotFresh->audit_ia['statut']);
        $this->assertNotEmpty($lotFresh->audit_ia['points_forts']);
    }

    public function test_audit_ia_detecte_anomalie_chronologique(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Fromage']);
        $lot = Lot::factory()->create([
            'produit_id' => $produit->id,
            'date_production' => today()->subDays(2),
        ]);
        $typeActeur = TypeActeur::factory()->create();
        $acteur = Acteur::factory()->create(['type_acteur_id' => $typeActeur->id]);

        // Étape antérieure à la date de production
        $etape = Etape::factory()->create([
            'lot_id' => $lot->id,
            'acteur_id' => $acteur->id,
            'type_etape' => 'production',
            'lieu' => 'Béja',
            'date_heure' => today()->subDays(10), // ANOMALIE
        ]);

        $iaService = app(\App\Services\TracabiliteIaService::class);
        $audit = $iaService->auditerParcoursLot($lot, force: true);

        $this->assertNotEmpty($audit['anomalies']);
        $this->assertStringContainsString('Incohérence temporelle', $audit['anomalies'][0]);

        // Invalidation du cache d'audit quand l'étape change
        $etape->update(['lieu' => 'Nouveau Lieu']);
        $this->assertNull($lot->fresh()->audit_ia);
    }

    public function test_conseil_ia_anti_gaspillage_adapte_a_la_peremption(): void
    {
        $produit = Produit::factory()->create(['nom' => 'Yaourt Nature']);

        // 1. Test Lot critique J-3
        $lotCritique = Lot::factory()->create([
            'produit_id' => $produit->id,
            'quantite' => 300,
            'date_production' => today()->subDays(10),
            'date_peremption' => today()->addDays(3),
        ]);

        $responseCritique = $this->actingAs($this->admin)->post(route('admin.lots.conseil-ia', $lotCritique));
        $responseCritique->assertRedirect();
        $responseCritique->assertSessionHas('success');

        $lotFreshCritique = $lotCritique->fresh();
        $this->assertNotNull($lotFreshCritique->conseil_ia);
        $this->assertSame('critique', $lotFreshCritique->conseil_ia['urgence']);
        $this->assertNotEmpty($lotFreshCritique->conseil_ia['actions']);

        // 2. Test Lot attention J-20
        $lotAttention = Lot::factory()->create([
            'produit_id' => $produit->id,
            'quantite' => 150,
            'date_production' => today()->subDays(5),
            'date_peremption' => today()->addDays(20),
        ]);

        $iaService = app(\App\Services\TracabiliteIaService::class);
        $conseilAttention = $iaService->genererConseilAntiGaspillage($lotAttention);
        $this->assertSame('attention', $conseilAttention['urgence']);

        // 3. Test Lot périmé
        $lotPerime = Lot::factory()->create([
            'produit_id' => $produit->id,
            'quantite' => 80,
            'date_production' => today()->subDays(30),
            'date_peremption' => today()->subDays(2),
        ]);

        $conseilPerime = $iaService->genererConseilAntiGaspillage($lotPerime);
        $this->assertSame('perime', $conseilPerime['urgence']);
    }
}

