<?php

namespace Database\Factories;

use App\Models\TypeActeur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TypeActeur>
 */
class TypeActeurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'libelle' => ucfirst(fake()->unique()->word()),
            'role_chaine' => fake()->sentence(6),
            'description' => fake()->sentence(14),
        ];
    }
}
