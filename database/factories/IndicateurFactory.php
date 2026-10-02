<?php

namespace Database\Factories;

use App\Models\EmpreinteCarbone;
use App\Models\Indicateur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indicateur>
 */
class IndicateurFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(Indicateur::UNITES));

        return [
            'empreinte_carbone_id' => EmpreinteCarbone::factory(),
            'etape_id' => null,
            'type' => $type,
            'valeur' => fake()->randomFloat(2, 1, 5000),
            'unite' => Indicateur::UNITES[$type],
        ];
    }
}
