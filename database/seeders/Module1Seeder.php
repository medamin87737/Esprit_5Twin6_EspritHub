<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Seeder;

class Module1Seeder extends Seeder
{
    /**
     * Catégories et produits du catalogue (Module 1 — Ghada).
     */
    public function run(): void
    {
        $catalogue = [
            ['Fruits de saison', 'fruits', 'Fruits frais cultivés en Tunisie, récoltés à maturité.', [
                ['Dattes Deglet Nour', 'Tozeur', 'Dattes naturelles, non sucrées.'],
                ['Oranges Maltaises', 'Nabeul', 'Oranges fraîches du Cap Bon.'],
                ['Grenades', 'Gabès', 'Grenades fraîches entières.'],
            ]],
            ['Légumes frais', 'legumes', 'Légumes de plein champ et de serre, vendus frais.', [
                ['Tomates de plein champ', 'Kairouan', 'Tomates fraîches.'],
                ['Piments verts', 'Sidi Bouzid', 'Piments frais.'],
                ['Pommes de terre', 'Jendouba', 'Pommes de terre de consommation.'],
            ]],
            ['Produits laitiers', 'laitiers', 'Lait, yaourts et fromages issus de laiteries locales.', [
                ['Yaourt nature', 'Béja', 'Lait entier pasteurisé, ferments lactiques.'],
                ['Lait demi-écrémé UHT 1 L', 'Mateur', 'Lait de vache demi-écrémé stérilisé UHT.'],
                ['Fromage frais', 'Bizerte', 'Lait pasteurisé, sel, présure.'],
            ]],
            ['Céréales et dérivés', 'cereales', 'Semoules, pâtes et farines de blé dur tunisien.', [
                ['Couscous moyen 1 kg', 'Sfax', 'Semoule de blé dur.'],
                ['Pâtes spaghetti 500 g', 'Sousse', 'Semoule de blé dur, eau.'],
                ['Farine de blé', 'Béja', 'Farine de blé tendre type 55.'],
            ]],
            ['Huiles et condiments', 'autres', 'Huiles d\'olive et condiments traditionnels.', [
                ['Huile d\'olive extra vierge 1 L', 'Sfax', 'Huile d\'olive extra vierge, première pression à froid.'],
                ['Harissa traditionnelle', 'Nabeul', 'Piments rouges, ail, sel, huile d\'olive, épices.'],
                ['Câpres au vinaigre', 'Kairouan', 'Câpres, vinaigre, sel.'],
            ]],
            ['Boissons', 'boissons', 'Eaux minérales et jus de fruits.', [
                ['Eau minérale naturelle 1,5 L', 'Zaghouan', 'Eau minérale naturelle.'],
                ['Jus d\'orange 1 L', 'Nabeul', 'Jus d\'orange à base de concentré.'],
                ['Citronnade', 'Tunis', 'Eau, citron, sucre.'],
            ]],
        ];

        // Les catégories absentes de cette liste restent gérées par l'administration.
        $proprietaireParType = [
            'legumes' => User::firstWhere('email', 'producteur@nutritrace.tn'),
            'cereales' => User::firstWhere('email', 'producteur@nutritrace.tn'),
            'laitiers' => User::firstWhere('email', 'transformateur@nutritrace.tn'),
        ];

        foreach ($catalogue as [$nom, $type, $description, $produits]) {
            $categorie = Categorie::factory()->create([
                'nom' => $nom,
                'type' => $type,
                'description' => $description,
            ]);

            foreach ($produits as [$produit, $ville, $composition]) {
                Produit::factory()->for($categorie)->create([
                    'proprietaire_id' => ($proprietaireParType[$type] ?? null)?->id,
                    'nom' => $produit,
                    'origine' => $ville.', Tunisie',
                    'description' => "{$produit} produit à {$ville}.",
                    'composition' => $composition,
                ]);
            }
        }
    }
}
