<?php

namespace Tests\Unit;

use App\Services\ClimatiqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClimatiqServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_climatiq_fournit_les_quatre_facteurs_officiels(): void
    {
        $service = app(ClimatiqService::class);
        $facteurs = $service->obtenirFacteurs();

        $this->assertArrayHasKey('eau', $facteurs);
        $this->assertArrayHasKey('energie', $facteurs);
        $this->assertArrayHasKey('transport', $facteurs);
        $this->assertArrayHasKey('emballage', $facteurs);

        $this->assertSame(0.0003, $facteurs['eau']['valeur']);
        $this->assertSame(0.47, $facteurs['energie']['valeur']);
        $this->assertSame(0.90, $facteurs['transport']['valeur']);
        $this->assertSame(2.50, $facteurs['emballage']['valeur']);

        $this->assertNotEmpty($facteurs['transport']['activite_id']);
        $this->assertNotEmpty($facteurs['transport']['source']);
    }

    public function test_climatiq_estimer_calcule_impact_correctement(): void
    {
        $service = app(ClimatiqService::class);
        $resultat = $service->estimer('transport', 100);

        $this->assertSame(90.0, $resultat['co2e']);
        $this->assertSame('kg CO₂e', $resultat['unite']);
        $this->assertStringContainsString('DEFRA', $resultat['source']);
    }
}
