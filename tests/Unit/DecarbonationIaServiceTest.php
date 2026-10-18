<?php

namespace Tests\Unit;

use App\Models\EmpreinteCarbone;
use App\Models\Indicateur;
use App\Models\Lot;
use App\Models\Produit;
use App\Services\DecarbonationIaService;
use Tests\TestCase;

class DecarbonationIaServiceTest extends TestCase
{
    public function test_analyser_genere_un_plan_decarbonation_complet(): void
    {
        $service = app(DecarbonationIaService::class);

        $empreinte = new EmpreinteCarbone([
            'co2_total' => 6.5,
            'score' => 'D',
            'methode' => 'ACV Simplifiée GHG Protocol',
            'date_calcul' => now(),
        ]);
        $empreinte->id = 999;

        // Indicateurs simulant une forte empreinte emballage
        $indEmballage = new Indicateur([
            'type' => 'emballage',
            'valeur' => 2.0,
            'unite' => 'kg',
        ]);
        $indTransport = new Indicateur([
            'type' => 'transport',
            'valeur' => 1.5,
            'unite' => 'km',
        ]);

        $empreinte->setRelation('indicateurs', collect([$indEmballage, $indTransport]));

        $audit = $service->analyser($empreinte);

        $this->assertIsArray($audit);
        $this->assertArrayHasKey('synthese', $audit);
        $this->assertArrayHasKey('point_critique', $audit);
        $this->assertArrayHasKey('actions', $audit);
        $this->assertArrayHasKey('projection', $audit);

        // Doit identifier l'emballage comme point critique (2kg * 2.5 = 5.0 kg CO2)
        $this->assertEquals('Emballage', $audit['point_critique']);
        $this->assertCount(3, $audit['actions']);

        // Doit projeter une amélioration vers C, B ou A
        $this->assertContains($audit['projection']['nouveau_score'], ['A', 'B', 'C']);
        $this->assertLessThan(6.5, $audit['projection']['co2_cible']);
    }
}
