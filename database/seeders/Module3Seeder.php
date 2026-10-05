<?php

namespace Database\Seeders;

use App\Models\Acteur;
use App\Models\Etape;
use App\Models\Lot;
use App\Models\Produit;
use Illuminate\Database\Seeder;

class Module3Seeder extends Seeder
{
    /**
     * Durée de conservation (jours) selon le type de catégorie du Module 1.
     */
    private const CONSERVATION = [
        'fruits' => 30,
        'legumes' => 15,
        'laitiers' => 21,
        'cereales' => 365,
        'boissons' => 270,
        'autres' => 540,
    ];

    /**
     * Ordre du parcours : type d'acteur, décalage en jours et heure.
     */
    private const PARCOURS = [
        'production' => ['Producteur', 0, '06:30'],
        'transformation' => ['Transformateur', 1, '09:00'],
        'distribution' => ['Distributeur', 2, '14:00'],
        'vente' => ['Détaillant', 4, '10:00'],
    ];

    /**
     * Lots et étapes de traçabilité (Module 3 — Ons).
     * Lancer Module1Seeder et ActeurSeeder avant : chaque étape est confiée
     * à un acteur qui prend en charge le produit du lot.
     */
    public function run(): void
    {
        $lots = [
            ['LOT-2026-0001', 'Huile d\'olive extra vierge 1 L', 1200, 120],
            ['LOT-2026-0002', 'Dattes Deglet Nour', 3000, 25],
            ['LOT-2026-0003', 'Yaourt nature', 800, 40],
            ['LOT-2026-0004', 'Couscous moyen 1 kg', 2500, 60],
            ['LOT-2026-0005', 'Harissa traditionnelle', 1500, 90],
            ['LOT-2026-0006', 'Oranges Maltaises', 2000, 12],
            ['LOT-2026-0007', 'Lait demi-écrémé UHT 1 L', 5000, 8],
            ['LOT-2026-0008', 'Tomates de plein champ', 1800, 7],
            ['LOT-2026-0009', 'Pâtes spaghetti 500 g', 4000, 45],
            ['LOT-2026-0010', 'Jus d\'orange 1 L', 2200, 30],
            ['LOT-2026-0011', 'Farine de blé', 900, 2],
        ];

        $produits = Produit::with('categorie')->get()->keyBy('nom');
        $acteurs = Acteur::with(['typeActeur', 'produits:id'])->orderBy('id')->get();

        foreach ($lots as [$numero, $nomProduit, $quantite, $joursDepuisProduction]) {
            $produit = $produits[$nomProduit] ?? null;

            if (! $produit) {
                continue;
            }

            $production = today()->subDays($joursDepuisProduction);

            $lot = Lot::factory()->for($produit)->create([
                'numero_lot' => $numero,
                'quantite' => $quantite,
                'date_production' => $production,
                'date_peremption' => $production->copy()->addDays(self::CONSERVATION[$produit->categorie?->type] ?? 180),
            ]);

            if ($joursDepuisProduction < 5) {
                continue;
            }

            foreach (self::PARCOURS as $type => [$typeActeur, $decalage, $heure]) {
                $candidats = $acteurs->filter(fn ($a) => $a->typeActeur?->libelle === $typeActeur && $a->produits->contains('id', $produit->id));
                $ville = str($produit->origine)->before(',')->toString();
                $acteur = $candidats->first(fn ($a) => str_contains($a->adresse, $ville)) ?? $candidats->first();

                if (! $acteur) {
                    continue;
                }

                Etape::factory()->for($lot)->for($acteur)->create([
                    'type_etape' => $type,
                    'date_heure' => $production->copy()->addDays($decalage)->setTimeFromTimeString($heure),
                    'lieu' => $acteur->adresse,
                    'mode_transport' => $this->transport($type, $produit->categorie?->type, $acteur->pays),
                    'remarques' => $this->remarque($type, $produit->categorie?->type),
                ]);
            }
        }
    }

    private function transport(string $type, ?string $categorie, string $pays): string
    {
        return match (true) {
            $type === 'production' => 'aucun',
            $pays !== 'Tunisie' => 'bateau',
            in_array($categorie, ['laitiers', 'fruits', 'legumes'], true) => 'camion_frigorifique',
            default => 'camion',
        };
    }

    private function remarque(string $type, ?string $categorie): ?string
    {
        return match (true) {
            $type === 'production' => 'Récolte ou fabrication contrôlée, lot étiqueté sur site.',
            $categorie === 'laitiers' => 'Chaîne du froid respectée entre 2 et 6 °C.',
            $type === 'vente' => 'Mise en rayon et contrôle des dates.',
            default => null,
        };
    }
}
