<?php

namespace Database\Factories;

use App\Models\Categorie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categorie>
 */
class CategorieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => ucfirst(fake()->unique()->words(2, true)),
            'type' => fake()->randomElement(array_keys(config('nutritrace.options.categorie_types'))),
            'description' => fake()->sentence(12),
        ];
    }
}
