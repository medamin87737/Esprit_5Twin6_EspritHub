<?php

namespace Database\Factories;

use App\Models\Lot;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Lot>
 */
class LotFactory extends Factory
{
    public function definition(): array
    {
        $production = Carbon::instance(fake()->dateTimeBetween('-6 months', '-1 week'))->startOfDay();

        return [
            'produit_id' => Produit::factory(),
            'numero_lot' => 'LOT-'.$production->year.'-'.fake()->unique()->numerify('####'),
            'quantite' => fake()->numberBetween(50, 5000),
            'date_production' => $production,
            'date_peremption' => $production->copy()->addDays(fake()->numberBetween(15, 365)),
        ];
    }
}
