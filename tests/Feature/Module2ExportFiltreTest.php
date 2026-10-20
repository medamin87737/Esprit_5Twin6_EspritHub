<?php

namespace Tests\Feature;

use App\Models\Analyse;
use App\Models\Laboratoire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 2 (Amin) — le téléchargement CSV doit exporter ce qui est filtré à l'écran.
 */
class Module2ExportFiltreTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true]);
    }

    public function test_export_laboratoires_sans_filtre_contient_tous_les_labos(): void
    {
        Laboratoire::factory()->create(['nom' => 'Labo Alpha Tunis', 'ville' => 'Tunis']);
        Laboratoire::factory()->create(['nom' => 'Labo Beta Sfax', 'ville' => 'Sfax']);

        $response = $this->actingAs($this->admin)->get(route('admin.laboratoires.export'));

        $response->assertOk();
        $response->assertDownload();
        $contenu = $response->streamedContent();
        $this->assertStringContainsString('Labo Alpha Tunis', $contenu);
        $this->assertStringContainsString('Labo Beta Sfax', $contenu);
    }

    public function test_export_laboratoires_respecte_le_filtre_ville(): void
    {
        Laboratoire::factory()->create(['nom' => 'Labo Alpha Tunis', 'ville' => 'Tunis']);
        Laboratoire::factory()->create(['nom' => 'Labo Beta Sfax', 'ville' => 'Sfax']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.laboratoires.export', ['ville' => 'Sfax']));

        $response->assertOk();
        $contenu = $response->streamedContent();
        $this->assertStringContainsString('Labo Beta Sfax', $contenu);
        $this->assertStringNotContainsString('Labo Alpha Tunis', $contenu);
    }

    public function test_export_laboratoires_respecte_la_recherche(): void
    {
        Laboratoire::factory()->create(['nom' => 'Labo Alpha Tunis', 'ville' => 'Tunis']);
        Laboratoire::factory()->create(['nom' => 'Labo Beta Sfax', 'ville' => 'Sfax']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.laboratoires.export', ['q' => 'Alpha']));

        $contenu = $response->streamedContent();
        $this->assertStringContainsString('Labo Alpha Tunis', $contenu);
        $this->assertStringNotContainsString('Labo Beta Sfax', $contenu);
    }

    public function test_export_analyses_respecte_le_filtre_resultat(): void
    {
        $conforme = Analyse::factory()->create(['resultat' => 'conforme']);
        $nonConforme = Analyse::factory()->create(['resultat' => 'non_conforme']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.analyses.export', ['resultat' => 'non_conforme']));

        $response->assertOk();
        $contenu = $response->streamedContent();
        $this->assertStringContainsString($nonConforme->numero, $contenu);
        $this->assertStringNotContainsString($conforme->numero, $contenu);
    }

    public function test_export_laboratoires_refuse_aux_non_admins(): void
    {
        $user = User::factory()->create(['role' => 'consommateur', 'active' => true]);

        $this->actingAs($user)->get(route('admin.laboratoires.export'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.laboratoires.export'))->assertRedirect(route('login'));
    }
}
