<?php

namespace Database\Factories;

use App\Models\EmpreinteCarbone;
use App\Models\Lot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmpreinteCarbone>
 */
class EmpreinteCarboneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lot_id' => Lot::factory(),
            'co2_total' => fake()->randomFloat(2, 0.2, 9),
            // Les seeders tournent sans événements de modèle : le hook « saving » ne calcule pas le score.
            'score' => fn (array $attributes) => EmpreinteCarbone::scorePour((float) $attributes['co2_total']),
            'methode' => fake()->randomElement(['ACV', 'Agribalyse', 'Bilan Carbone']),
            'date_calcul' => today(),
        ];
    }
}
