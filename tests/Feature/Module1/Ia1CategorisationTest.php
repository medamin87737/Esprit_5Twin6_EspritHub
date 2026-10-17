<?php

declare(strict_types=1);

namespace Tests\Feature\Module1;

use App\Models\Categorie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests IA1 — catégorisation + composition (Module 1, Ghada).
 * Http::fake() : aucun vrai appel API.
 */
class Ia1CategorisationTest extends TestCase
{
    use RefreshDatabase;

    private string $route;

    protected function setUp(): void
    {
        parent::setUp();
        $this->route = route('ia.produits.categoriser');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function consommateur(): User
    {
        return User::factory()->create(['role' => 'consommateur']);
    }

    private function reponseOpenRouter(array $json): string
    {
        return json_encode([
            'choices' => [[
                'message' => ['content' => json_encode($json)],
            ]],
        ]);
    }

    // ─── Autorisation ─────────────────────────────────────────────────────────

    public function test_visiteur_non_connecte_reçoit_401(): void
    {
        $this->postJson($this->route, ['nom' => 'Yaourt'])->assertUnauthorized();
    }

    public function test_consommateur_reçoit_403(): void
    {
        $this->actingAs($this->consommateur());
        $this->postJson($this->route, ['nom' => 'Yaourt'])->assertForbidden();
    }

    // ─── Réponse OpenRouter simulée ───────────────────────────────────────────

    public function test_reponse_openrouter_valide_retourne_categorie(): void
    {
        config(['ia.cle' => 'fake-key-test']);

        $cat = Categorie::factory()->create(['nom' => 'Yaourts', 'type' => 'laitiers']);

        Http::fake([
            '*/chat/completions' => Http::response($this->reponseOpenRouter([
                'categorie_id' => $cat->id,
                'type' => 'laitiers',
                'composition' => 'Lait écrémé, ferments',
                'description' => 'Yaourt nature',
                'confiance' => 0.95,
                'justification' => 'Produit laitier fermenté',
            ]), 200),
        ]);

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, [
            'nom' => 'Yaourt nature',
            'description' => 'Yaourt nature bio',
        ]);

        $reponse->assertOk()
            ->assertJsonPath('categorie_id', $cat->id)
            ->assertJsonPath('type', 'laitiers');
    }

    public function test_categorie_id_invalide_est_corrige_par_le_type(): void
    {
        config(['ia.cle' => 'fake-key-test']);

        $cat = Categorie::factory()->create(['nom' => 'Légumes frais', 'type' => 'legumes']);

        Http::fake([
            '*/chat/completions' => Http::response($this->reponseOpenRouter([
                'categorie_id' => 9999, // n'existe pas
                'type' => 'legumes',
                'composition' => null,
                'description' => null,
                'confiance' => 0.7,
                'justification' => 'Légume',
            ]), 200),
        ]);

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, ['nom' => 'Carotte bio']);

        $reponse->assertOk()
            ->assertJsonPath('categorie_id', $cat->id);

        // Doit avoir un avertissement
        $this->assertNotNull($reponse->json('avertissement'));
    }

    public function test_erreur_402_credit_insuffisant_declenche_repli_local(): void
    {
        config(['ia.cle' => 'fake-key-test']);

        $cat = Categorie::factory()->create(['type' => 'laitiers']);

        Http::fake([
            '*/chat/completions' => Http::response(['error' => 'insufficient credits'], 402),
        ]);

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, ['nom' => 'Lait entier']);

        // Repli local → 200 avec avertissement
        $reponse->assertOk();
        $this->assertStringContainsString('402', $reponse->json('avertissement') ?? '');
    }

    public function test_sans_cle_utilise_repli_local(): void
    {
        config(['ia.cle' => null]);

        $cat = Categorie::factory()->create(['type' => 'laitiers']);

        Http::fake(); // aucun appel ne doit être fait

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, ['nom' => 'Fromage blanc']);

        $reponse->assertOk();
        // Aucun appel HTTP ne doit avoir été fait
        Http::assertNothingSent();
    }

    // ─── Repli local ──────────────────────────────────────────────────────────

    public function test_repli_local_yaourt_est_classe_laitier(): void
    {
        config(['ia.cle' => null]);
        Categorie::factory()->create(['type' => 'laitiers']);

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, ['nom' => 'Yaourt à la fraise']);

        $reponse->assertOk()
            ->assertJsonPath('type', 'laitiers');
    }

    public function test_repli_local_jus_orange_est_classe_boisson(): void
    {
        config(['ia.cle' => null]);
        Categorie::factory()->create(['type' => 'boissons']);

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, ['nom' => 'Jus d\'orange']);

        $reponse->assertOk()
            ->assertJsonPath('type', 'boissons');
    }

    // ─── Réponse entourée de ```json (tolérance) ──────────────────────────────

    public function test_reponse_avec_balises_json_est_parsee(): void
    {
        config(['ia.cle' => 'fake-key-test']);

        $cat = Categorie::factory()->create(['type' => 'cereales']);

        $contenuBrut = "```json\n".json_encode([
            'categorie_id' => $cat->id,
            'type' => 'cereales',
            'composition' => null,
            'description' => null,
            'confiance' => 0.8,
            'justification' => 'Céréale',
        ])."\n```";

        Http::fake([
            '*/chat/completions' => Http::response(json_encode([
                'choices' => [['message' => ['content' => $contenuBrut]]],
            ]), 200),
        ]);

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, ['nom' => 'Pain complet']);

        $reponse->assertOk()->assertJsonPath('type', 'cereales');
    }
}
