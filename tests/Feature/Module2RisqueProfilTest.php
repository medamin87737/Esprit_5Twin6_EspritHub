<?php

namespace Tests\Feature;

use App\Models\Analyse;
use App\Models\Lot;
use App\Models\Produit;
use App\Services\Analyses\AnalyseIaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 2 (Amin) — le score IA doit changer selon le profil santé déclaré.
 */
class Module2RisqueProfilTest extends TestCase
{
    use RefreshDatabase;

    private function lotAvecProduit(string $nom, string $composition): Lot
    {
        $produit = Produit::factory()->create([
            'nom' => $nom,
            'composition' => $composition,
            'description' => $composition,
        ]);
        $lot = Lot::factory()->create(['produit_id' => $produit->id]);
        Analyse::factory()->create(['lot_id' => $lot->id, 'resultat' => 'conforme']);

        return $lot->fresh(['analyses.laboratoire', 'produit.categorie']);
    }

    public function test_score_change_selon_profil_diabete_vs_hypertension(): void
    {
        $lot = $this->lotAvecProduit('Dattes au miel', 'dattes, miel, sucre');
        $service = app(AnalyseIaService::class);

        $sansProfil = $service->evaluerRisque($lot);
        $diabete = $service->evaluerRisque($lot, 'diabète de type 2');
        $hypertension = $service->evaluerRisque($lot, 'hypertension, je limite le sel');

        // Le diabète doit donner un score nettement plus élevé sur ce produit sucré
        $this->assertGreaterThan($sansProfil['score'], $diabete['score']);
        $this->assertGreaterThanOrEqual(25, $diabete['score_profil']);
        // L'hypertension ne trouve pas de sel : pénalité faible ou nulle
        $this->assertLessThan($diabete['score'], $hypertension['score']);

        // Détails présents et aucun disclaimer
        $this->assertNotEmpty($diabete['facteurs']);
        $this->assertNotEmpty($diabete['conseils']);
        $this->assertSame('Diabète / sucre', $diabete['profils_detectes'][0]['label']);
        $this->assertStringNotContainsString('avis médical', $diabete['justification'].' '.($diabete['conseil_sante'] ?? ''));
    }

    public function test_diabete_soda_boga_deconseille_meme_sans_mention_sucre(): void
    {
        // « Boga cidre » : la composition ne dit pas « sucre », mais c'est un soda.
        $lot = $this->lotAvecProduit('Boga cidre', 'eau gazéifiée, arômes');
        $service = app(AnalyseIaService::class);

        $diabete = $service->evaluerRisque($lot, 'diabète de type 2');
        $sansProfil = $service->evaluerRisque($lot);

        $this->assertGreaterThanOrEqual(25, $diabete['score_profil']);
        $this->assertSame('deconseille', $diabete['aptitude']);
        $this->assertGreaterThan($sansProfil['score'], $diabete['score']);
        $libelles = array_column($diabete['facteurs'], 'libelle');
        $this->assertContains('Boisson gazeuse sucrée (soda)', $libelles);
    }

    public function test_diabete_eau_minerale_reste_adaptee(): void
    {
        $lot = $this->lotAvecProduit('Eau minérale naturelle 1,5 L', 'eau minérale naturelle');

        $diabete = app(AnalyseIaService::class)->evaluerRisque($lot, 'diabète');

        $this->assertSame('adapte', $diabete['aptitude']);
        $this->assertLessThanOrEqual(0, $diabete['score_profil']);
    }

    public function test_score_hypertension_sur_produit_sale(): void
    {
        $lot = $this->lotAvecProduit('Fromage en saumure', 'lait, sel, saumure');
        $service = app(AnalyseIaService::class);

        $hypertension = $service->evaluerRisque($lot, 'hypertension');
        $diabete = $service->evaluerRisque($lot, 'diabète');

        $this->assertGreaterThan($diabete['score'], $hypertension['score']);
        $this->assertGreaterThanOrEqual(18, $hypertension['score_profil']);
    }

    public function test_resume_structure_en_quatre_sections(): void
    {
        $lot = $this->lotAvecProduit('Huile d olive profil', 'huile d olive');
        $analyse = $lot->analyses->first();

        $resume = app(AnalyseIaService::class)->resumer($analyse);

        $this->assertStringContainsString('Verdict :', $resume);
        $this->assertStringContainsString('Points confirmés :', $resume);
        $this->assertStringContainsString('Points de vigilance :', $resume);
        $this->assertStringContainsString('Recommandation :', $resume);
    }
}
