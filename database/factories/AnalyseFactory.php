<?php

namespace Database\Factories;

use App\Models\Analyse;
use App\Models\Laboratoire;
use App\Models\Lot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Analyse>
 */
class AnalyseFactory extends Factory
{
    public function definition(): array
    {
        $prelevement = fake()->dateTimeBetween('-2 months', '-1 week');

        return [
            'lot_id' => Lot::factory(),
            'laboratoire_id' => Laboratoire::factory(),
            'numero' => 'ANA-'.now()->year.'-'.fake()->unique()->numerify('9###'),
            'type' => fake()->randomElement(array_keys(Analyse::ICONES)),
            'date_prelevement' => $prelevement,
            'date_resultat' => (clone $prelevement)->modify('+3 days'),
            'resultat' => 'conforme',
        ];
    }

    public function enAttente(): static
    {
        return $this->state(fn (array $attributes) => ['resultat' => 'en_attente', 'date_resultat' => null]);
    }

    public function nonConforme(): static
    {
        return $this->state(fn (array $attributes) => [
            'resultat' => 'non_conforme',
            'commentaire' => 'Teneur supérieure à la limite réglementaire.',
        ]);
    }
}
