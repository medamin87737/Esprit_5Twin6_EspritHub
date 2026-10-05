<?php

namespace Database\Seeders;

use App\Models\Acteur;
use App\Models\Categorie;
use App\Models\TypeActeur;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActeurSeeder extends Seeder
{
    /**
     * Types d'acteurs, acteurs (fiches société) et produits pris en charge.
     * Les produits viennent du Module 1 : lancer Module1Seeder avant.
     */
    public function run(): void
    {
        $types = collect([
            'Producteur' => ['Cultive ou élève les matières premières agricoles.', 'Exploitations agricoles, vergers, oasis et coopératives.'],
            'Transformateur' => ['Transforme les matières premières en produits finis.', 'Laiteries, semouleries, huileries et conserveries.'],
            'Distributeur' => ['Achemine et stocke les produits jusqu\'aux points de vente.', 'Grossistes, centrales d\'achat et logisticiens.'],
            'Détaillant' => ['Vend les produits au consommateur final.', 'Épiceries, marchés et magasins spécialisés.'],
        ])->map(fn ($infos, $libelle) => TypeActeur::factory()->create([
            'libelle' => $libelle,
            'role_chaine' => $infos[0],
            'description' => $infos[1],
        ]));

        $acteurs = [
            ['Ferme El Baraka', 'Producteur', 'Route de Téboursouk, Béja', 'Tunisie', 36.7256, 9.1817, ['Légumes frais', 'Céréales et dérivés']],
            ['Domaine des Oasis', 'Producteur', 'Zone des palmeraies, Tozeur', 'Tunisie', 33.9197, 8.1335, ['Fruits de saison']],
            ['Coopérative oléicole du Sahel', 'Producteur', 'Route de Gabès km 4, Sfax', 'Tunisie', 34.7406, 10.7603, ['Huiles et condiments']],
            ['Vergers du Cap Bon', 'Producteur', 'Avenue Habib Bourguiba, Nabeul', 'Tunisie', 36.4561, 10.7376, ['Fruits de saison']],
            ['Laiterie du Nord', 'Transformateur', 'Zone industrielle, Mateur', 'Tunisie', 37.0400, 9.6650, ['Produits laitiers']],
            ['Semoulerie du Sahel', 'Transformateur', 'Zone industrielle Sidi Abdelhamid, Sousse', 'Tunisie', 35.8256, 10.6360, ['Céréales et dérivés']],
            ['Conserverie Dar Nabeul', 'Transformateur', 'Rue des Potiers, Nabeul', 'Tunisie', 36.4513, 10.7357, ['Huiles et condiments']],
            ['Carthage Frais Distribution', 'Distributeur', 'Marché de gros, Bir El Kassaa', 'Tunisie', 36.7530, 10.2280, ['Fruits de saison', 'Légumes frais', 'Produits laitiers']],
            ['Sahel Logistique Alimentaire', 'Distributeur', 'Route de Monastir, Sousse', 'Tunisie', 35.8300, 10.5900, ['Céréales et dérivés', 'Boissons']],
            ['Fresh Med Import', 'Distributeur', 'Marché d\'intérêt national, Marseille', 'France', 43.3460, 5.3850, ['Fruits de saison', 'Huiles et condiments']],
            ['Épicerie fine Sidi Bou', 'Détaillant', 'Rue Habib Thameur, Sidi Bou Saïd', 'Tunisie', 36.8687, 10.3416, ['Huiles et condiments', 'Boissons']],
            ['Marché Bio de La Marsa', 'Détaillant', 'Place du Saf-Saf, La Marsa', 'Tunisie', 36.8782, 10.3247, ['Fruits de saison', 'Légumes frais']],
        ];

        $produitsParCategorie = Categorie::with('produits:id,categorie_id')->get()
            ->mapWithKeys(fn ($categorie) => [$categorie->nom => $categorie->produits->pluck('id')]);

        foreach ($acteurs as [$nom, $type, $adresse, $pays, $latitude, $longitude, $categories]) {
            $acteur = Acteur::factory()->for($types[$type])->create([
                'nom' => $nom,
                'email' => 'contact@'.str($nom)->ascii()->slug().'.'.($pays === 'France' ? 'fr' : 'tn'),
                'adresse' => $adresse,
                'pays' => $pays,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            if ($compte = User::firstWhere(['name' => $nom, 'role' => strtolower($type)])) {
                $acteur->user()->associate($compte)->save();
            }

            $acteur->produits()->sync(
                collect($categories)->flatMap(fn ($categorie) => $produitsParCategorie[$categorie] ?? [])->all()
            );
        }
    }
}
