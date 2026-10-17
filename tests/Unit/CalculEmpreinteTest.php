<?php

namespace Tests\Unit;

use App\Models\EmpreinteCarbone;
use App\Models\Indicateur;
use App\Models\Lot;
use App\Support\CalculEmpreinte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculEmpreinteTest extends TestCase
{
    use RefreshDatabase;

    public function test_co2_par_unite_suit_valeur_fois_facteur_divise_quantite(): void
    {
        config(['nutritrace.facteurs_co2.transport' => 0.9]);

        $lot = Lot::factory()->create(['quantite' => 100]);
        $indicateur = Indicateur::factory()->make([
            'type' => 'transport',
            'valeur' => 200,
        ]);

        $this->assertSame(1.8, CalculEmpreinte::contributionCo2($indicateur, $lot));
    }

    public function test_synchroniser_recalcule_co2_et_score_apres_indicateur(): void
    {
        config(['nutritrace.facteurs_co2.transport' => 0.9]);

        $lot = Lot::factory()->create(['quantite' => 100]);
        $empreinte = EmpreinteCarbone::factory()->for($lot)->create([
            'co2_total' => 99,
            'score' => 'E',
        ]);

        Indicateur::factory()->for($empreinte)->create([
            'type' => 'transport',
            'valeur' => 200,
            'unite' => 'km',
        ]);

        $empreinte->refresh();

        $this->assertSame(1.8, $empreinte->co2_total);
        $this->assertSame('B', $empreinte->score);
    }
}
