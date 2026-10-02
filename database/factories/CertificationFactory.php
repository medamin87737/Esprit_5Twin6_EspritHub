<?php

namespace Database\Factories;

use App\Models\Certification;
use App\Models\Organisme;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['bio', 'local', 'equitable']);
        $obtention = fake()->dateTimeBetween('-2 years', '-1 month');

        return [
            'organisme_id' => Organisme::factory(),
            'produit_id' => Produit::factory(),
            'type' => $type,
            'numero' => strtoupper(substr($type, 0, 3)).'-TN-'.fake()->unique()->numerify('####-###'),
            'statut' => 'valide',
            'date_obtention' => $obtention,
            'date_expiration' => (clone $obtention)->modify('+3 years'),
        ];
    }

    public function expiree(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'expiree',
            'date_obtention' => today()->subYears(3),
            'date_expiration' => today()->subMonths(2),
        ]);
    }

    public function suspendue(): static
    {
        return $this->state(fn (array $attributes) => ['statut' => 'suspendue']);
    }
}
