<?php

declare(strict_types=1);

namespace Tests\Feature\Module1;

use App\Models\Categorie;
use App\Models\EmpreinteCarbone;
use App\Models\Lot;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests IA3 — Recommandations par embeddings (Module 1, Ghada).
 * Http::fake() : aucun vrai appel API.
 */
class Ia3EmbeddingsTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function creerProduitAvecScore(float $co2, string $nom = 'Produit'): Produit
    {
        $produit = Produit::factory()->create(['nom' => $nom]);
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);
        EmpreinteCarbone::factory()->for($lot)->create(['co2_total' => $co2]);

        return $produit->fresh();
    }

    // ─── Route accessible ─────────────────────────────────────────────────────

    public function test_route_recommandations_retourne_json(): void
    {
        $produit = Produit::factory()->create();

        $this->getJson(route('front.produits.recommandations', $produit))
            ->assertOk()
            ->assertJsonStructure(['html', 'source', 'avertissement']);
    }

    public function test_recommandations_avec_aucun_candidat(): void
    {
        $produit = $this->creerProduitAvecScore(0.1, 'Très écologique');

        $data = $this->getJson(route('front.produits.recommandations', $produit))->json();

        $this->assertStringContainsString('Aucune alternative', $data['html']);
    }

    // ─── Repli local (sans clé) ───────────────────────────────────────────────

    public function test_repli_local_sans_cle_fonctionne(): void
    {
        config(['ia.cle' => null]);

        $produit = $this->creerProduitAvecScore(5.0, 'Produit polluant');
        $produit->load('categorie');

        $eco = $this->creerProduitAvecScore(0.5, 'Produit écologique');
        $eco->load('categorie');

        $data = $this->getJson(route('front.produits.recommandations', $produit))->json();

        $this->assertSame('repli_local', $data['source']);
        $this->assertStringContainsString('Produit écologique', $data['html']);
    }

    // ─── Produit plus polluant jamais proposé ─────────────────────────────────

    public function test_produit_plus_polluant_jamais_propose(): void
    {
        config(['ia.cle' => null]);

        $produit = $this->creerProduitAvecScore(2.0, 'Produit référence');

        $moinsPolluant = $this->creerProduitAvecScore(1.0, 'Moins polluant');
        $plusPolluant = $this->creerProduitAvecScore(3.0, 'Plus polluant');

        $data = $this->getJson(route('front.produits.recommandations', $produit))->json();

        $this->assertStringContainsString('Moins polluant', $data['html']);
        $this->assertStringNotContainsString('Plus polluant', $data['html']);
    }

    // ─── Embeddings simulés + cache ───────────────────────────────────────────

    public function test_embeddings_simules_mis_en_cache(): void
    {
        config(['ia.cle' => 'fake-key-test']);

        $cat = Categorie::factory()->create(['type' => 'laitiers']);

        $produit = Produit::factory()->create(['nom' => 'Yaourt nature', 'categorie_id' => $cat->id]);
        $eco = Produit::factory()->create(['nom' => 'Yaourt bio', 'categorie_id' => $cat->id]);
        $lotP = Lot::factory()->create(['produit_id' => $produit->id]);
        $lotE = Lot::factory()->create(['produit_id' => $eco->id]);
        EmpreinteCarbone::factory()->for($lotP)->create(['co2_total' => 3.0]);
        EmpreinteCarbone::factory()->for($lotE)->create(['co2_total' => 0.5]);

        Http::fake([
            '*/embeddings' => Http::response([
                'data' => [
                    ['embedding' => [0.1, 0.2, 0.3], 'index' => 0], // yaourt nature
                    ['embedding' => [0.11, 0.19, 0.31], 'index' => 1], // yaourt bio
                ],
            ], 200),
        ]);

        // 1er appel : doit faire un appel HTTP
        $reponse1 = $this->getJson(route('front.produits.recommandations', $produit));
        $reponse1->assertOk();
        $this->assertSame('openrouter', $reponse1->json('source'));

        Http::assertSentCount(1);

        // Maintenant on pré-remplit le cache en DB (simule la commande)
        $produit->refresh();
        $eco->refresh();
        DB::table('produits')->where('id', $produit->id)->update([
            'embedding' => json_encode([0.1, 0.2, 0.3]),
            'embedding_hash' => $produit->hashEmbeddingAttendu(),
        ]);
        DB::table('produits')->where('id', $eco->id)->update([
            'embedding' => json_encode([0.11, 0.19, 0.31]),
            'embedding_hash' => $eco->hashEmbeddingAttendu(),
        ]);

        // 2e appel : pas d'appel HTTP (embeddings en cache)
        Http::fake(); // reset pour compter

        $reponse2 = $this->getJson(route('front.produits.recommandations', $produit));
        $reponse2->assertOk();

        Http::assertNothingSent();
    }

    // ─── Erreur API → repli local ─────────────────────────────────────────────

    public function test_erreur_api_declenche_repli_local(): void
    {
        config(['ia.cle' => 'fake-key-test']);

        $produit = $this->creerProduitAvecScore(5.0, 'Yaourt polluant');
        $eco = $this->creerProduitAvecScore(0.5, 'Yaourt écologique');

        Http::fake([
            '*/embeddings' => Http::response(['error' => 'rate limited'], 429),
        ]);

        $reponse = $this->getJson(route('front.produits.recommandations', $produit));

        $reponse->assertOk();
        $this->assertSame('repli_local', $reponse->json('source'));
        $this->assertNotNull($reponse->json('avertissement'));
    }

    // ─── Produit sans score → candidats A ou B ────────────────────────────────

    public function test_produit_sans_score_propose_scores_a_ou_b(): void
    {
        config(['ia.cle' => null]);

        $produit = Produit::factory()->create(); // pas de lot, pas de score

        $scoreA = $this->creerProduitAvecScore(0.5, 'Score A');
        $scoreB = $this->creerProduitAvecScore(1.5, 'Score B');
        $scoreD = $this->creerProduitAvecScore(5.0, 'Score D');

        $data = $this->getJson(route('front.produits.recommandations', $produit))->json();

        $this->assertStringContainsString('Score A', $data['html']);
        $this->assertStringContainsString('Score B', $data['html']);
        $this->assertStringNotContainsString('Score D', $data['html']);
    }

    // ─── Commande produits:embeddings ─────────────────────────────────────────

    public function test_commande_embeddings_sans_cle_utilise_repli(): void
    {
        config(['ia.cle' => null]);

        Produit::factory()->count(3)->create();

        $this->artisan('produits:embeddings')
            ->assertSuccessful();

        // Vérifie que les embeddings ont été stockés
        $this->assertDatabaseHas('produits', [
            'id' => Produit::first()->id,
        ]);
        $this->assertNotNull(Produit::first()->embedding_hash);
    }

    public function test_commande_embeddings_avec_force_recalcule_tout(): void
    {
        config(['ia.cle' => null]);

        Produit::factory()->create(['nom' => 'Test force']);

        // Premier calcul
        $this->artisan('produits:embeddings')->assertSuccessful();
        $hash1 = Produit::first()->embedding_hash;

        // Force recalcule
        $this->artisan('produits:embeddings', ['--force' => true])->assertSuccessful();
        $hash2 = Produit::first()->embedding_hash;

        // Le hash doit être identique (même texte, même modèle)
        $this->assertSame($hash1, $hash2);
    }

    // ─── EAN-13 déjà testé dans IA2, pas besoin de retester ici ─────────────────
}
