<?php

namespace Database\Factories;

use App\Models\Laboratoire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laboratoire>
 */
class LaboratoireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => 'Laboratoire '.fake()->unique()->lastName(),
            'ville' => fake()->randomElement(['Tunis', 'Sfax', 'Sousse', 'Bizerte', 'Nabeul']),
            'pays' => 'Tunisie',
            'accreditation' => 'TUNAC ISO/IEC 17025 n° 1-'.fake()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'telephone' => '+216 7'.fake()->numerify('# ### ###'),
        ];
    }
}
