<?php

namespace Database\Factories;

use App\Models\Organisme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organisme>
 */
class OrganismeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->unique()->company().' Certification',
            'pays' => fake()->randomElement(['Tunisie', 'France', 'Italie', 'Allemagne']),
            'site_web' => 'https://'.fake()->unique()->domainName(),
            'accreditation' => 'ISO/IEC 17065 n° '.fake()->numerify('####'),
        ];
    }
}
