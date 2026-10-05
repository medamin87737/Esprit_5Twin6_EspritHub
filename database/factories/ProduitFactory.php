<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produit>
 */
class ProduitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'categorie_id' => Categorie::factory(),
            'nom' => ucfirst(fake()->words(3, true)),
            'code_barres' => fake()->unique()->ean13(),
            'origine' => fake()->randomElement(['Béja', 'Nabeul', 'Sfax', 'Bizerte', 'Kairouan', 'Jendouba', 'Sousse']).', Tunisie',
            'description' => fake()->sentence(15),
            'composition' => fake()->sentence(8),
        ];
    }

    public function pourProprietaire(?User $proprietaire = null): static
    {
        return $this->state(fn (array $attributes) => [
            'proprietaire_id' => $proprietaire?->id ?? User::factory()->professionnel(),
        ]);
    }
}
