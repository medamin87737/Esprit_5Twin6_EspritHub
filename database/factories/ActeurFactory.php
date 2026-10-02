<?php

namespace Database\Factories;

use App\Models\Acteur;
use App\Models\TypeActeur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Acteur>
 */
class ActeurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type_acteur_id' => TypeActeur::factory(),
            'nom' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'telephone' => '+216 '.fake()->numerify('## ### ###'),
            'date_inscription' => fake()->dateTimeBetween('-3 years', 'now'),
            'adresse' => fake()->streetAddress(),
            'pays' => 'Tunisie',
            'latitude' => fake()->latitude(30, 37.5),
            'longitude' => fake()->longitude(7.5, 11.5),
        ];
    }
}
