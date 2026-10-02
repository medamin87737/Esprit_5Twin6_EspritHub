<?php

namespace Database\Factories;

use App\Models\Acteur;
use App\Models\Etape;
use App\Models\Lot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Etape>
 */
class EtapeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lot_id' => Lot::factory(),
            'acteur_id' => Acteur::factory(),
            'type_etape' => fake()->randomElement(array_keys(config('nutritrace.options.etape_types'))),
            'date_heure' => fake()->dateTimeBetween('-5 days', 'now'),
            'lieu' => fake()->city(),
            'mode_transport' => fake()->randomElement(array_keys(config('nutritrace.options.modes_transport'))),
            'remarques' => fake()->optional()->sentence(),
        ];
    }
}
