<?php

declare(strict_types=1);

namespace Tests\Feature\Module1;

use App\Models\Categorie;
use App\Models\User;
use App\Services\Module1\OpenRouterOcr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests IA2 — OCR d'étiquette (Module 1, Ghada).
 * Http::fake() : aucun vrai appel API.
 */
class Ia2OcrTest extends TestCase
{
    use RefreshDatabase;

    private string $route;

    protected function setUp(): void
    {
        parent::setUp();
        $this->route = route('ia.produits.ocr');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function image(): UploadedFile
    {
        // GD n'est pas installé sur ce poste : on crée un fichier binaire
        // avec le bon MIME type sans générer d'image réelle.
        return UploadedFile::fake()->create('etiquette.jpg', 100, 'image/jpeg');
    }

    private function reponseOpenRouterVision(array $json): string
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
        $this->postJson($this->route, ['image' => $this->image()])->assertUnauthorized();
    }

    public function test_fichier_non_image_reçoit_422(): void
    {
        $this->actingAs($this->admin());

        $fichier = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->postJson($this->route, ['image' => $fichier])
            ->assertStatus(422);
    }

    // ─── Sans clé : 503 ───────────────────────────────────────────────────────

    public function test_sans_cle_retourne_503(): void
    {
        config(['ia.cle' => null]);

        $this->actingAs($this->admin());

        $this->postJson($this->route, ['image' => $this->image()])
            ->assertStatus(503)
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'Clé'));
    }

    // ─── OCR simulé ───────────────────────────────────────────────────────────

    public function test_ocr_simule_retourne_champs_extraits(): void
    {
        config(['ia.cle' => 'fake-key-test']);

        Categorie::factory()->create(['type' => 'laitiers']);

        Http::fake([
            '*/chat/completions' => function ($req) {
                // Distinguer vision (image_url) de texte (pas d'image)
                $body = json_decode($req->body(), true);
                $content = $body['messages'][0]['content'] ?? [];
                $isVision = is_array($content) && collect($content)->contains(fn ($c) => ($c['type'] ?? '') === 'image_url');

                if ($isVision) {
                    return Http::response($this->reponseOpenRouterVision([
                        'texte' => 'Yaourt nature bio\nLait écrémé\nCode: 6191234567890',
                        'nom' => 'Yaourt nature bio',
                        'code_barres' => '6191234567890',
                        'origine' => 'Tunisie',
                        'composition' => 'Lait écrémé, ferments lactiques',
                        'description' => 'Yaourt nature',
                    ]), 200);
                }

                // Appel IA1 (catégorisation après OCR)
                return Http::response($this->reponseOpenRouterVision([
                    'categorie_id' => Categorie::first()->id,
                    'type' => 'laitiers',
                    'composition' => null,
                    'description' => null,
                    'confiance' => 0.9,
                    'justification' => 'Produit laitier',
                ]), 200);
            },
        ]);

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, ['image' => $this->image()]);

        $reponse->assertOk()
            ->assertJsonPath('nom', 'Yaourt nature bio')
            ->assertJsonPath('origine', 'Tunisie')
            ->assertJsonPath('composition', 'Lait écrémé, ferments lactiques');
    }

    // ─── EAN-13 invalide ──────────────────────────────────────────────────────

    public function test_code_barres_invalide_est_signale(): void
    {
        config(['ia.cle' => 'fake-key-test']);

        Categorie::factory()->create();

        Http::fake([
            '*/chat/completions' => function ($req) {
                $body = json_decode($req->body(), true);
                $content = $body['messages'][0]['content'] ?? [];
                $isVision = is_array($content) && collect($content)->contains(fn ($c) => ($c['type'] ?? '') === 'image_url');

                if ($isVision) {
                    return Http::response($this->reponseOpenRouterVision([
                        'texte' => 'Produit test',
                        'nom' => 'Produit test',
                        'code_barres' => '1234567890123', // EAN invalide (clé de contrôle)
                        'origine' => null,
                        'composition' => null,
                        'description' => null,
                    ]), 200);
                }

                return Http::response($this->reponseOpenRouterVision([
                    'categorie_id' => Categorie::first()->id,
                    'type' => 'autres',
                    'confiance' => 0.5,
                    'justification' => 'test',
                ]), 200);
            },
        ]);

        $this->actingAs($this->admin());

        $reponse = $this->postJson($this->route, ['image' => $this->image()]);

        $reponse->assertOk();
        $this->assertFalse($reponse->json('code_barres_valide'));
        $this->assertNotNull($reponse->json('code_barres_avertissement'));
    }

    // ─── Vérification EAN-13 (test unitaire de la méthode) ─────────────────────

    public function test_verifier_ean13_valide(): void
    {
        // 6191234567890 : code valide utilisé par les tests ProduitFactory
        // On le construit avec une clé valide
        $code = '619123456789'.'0'; // on va calculer la clé réelle

        // Recalculer : 619123456789 + clé
        $base = '619123456789';
        $somme = 0;
        for ($i = 0; $i < 12; $i++) {
            $somme += (int) $base[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $cle = (10 - ($somme % 10)) % 10;
        $codeValide = $base.$cle;

        $this->assertTrue(OpenRouterOcr::verifierEan13($codeValide));
    }

    public function test_verifier_ean13_invalide(): void
    {
        $this->assertFalse(OpenRouterOcr::verifierEan13('1234567890123'));
    }

    public function test_verifier_ean13_trop_court(): void
    {
        $this->assertFalse(OpenRouterOcr::verifierEan13('12345'));
    }
}
