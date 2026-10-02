<?php

namespace Database\Seeders;

use App\Models\Acteur;
use App\Models\EmpreinteCarbone;
use App\Models\Indicateur;
use App\Models\Lot;
use Illuminate\Database\Seeder;

class Module4Seeder extends Seeder
{
    /**
     * kg CO₂e par kg de produit, inspirés des ordres de grandeur Agribalyse.
     */
    private const CO2 = [
        'Huile d\'olive extra vierge 1 L' => 4.60,
        'Dattes Deglet Nour' => 0.95,
        'Yaourt nature' => 1.65,
        'Couscous moyen 1 kg' => 1.30,
        'Harissa traditionnelle' => 2.40,
        'Oranges Maltaises' => 0.48,
        'Lait demi-écrémé UHT 1 L' => 1.38,
        'Tomates de plein champ' => 0.72,
        'Pâtes spaghetti 500 g' => 1.52,
    ];

    /**
     * Litres d'eau par unité produite, selon le type de catégorie.
     */
    private const EAU = ['fruits' => 0.8, 'legumes' => 0.5, 'laitiers' => 1.0, 'cereales' => 1.2, 'autres' => 2.5, 'boissons' => 1.5];

    /**
     * Empreintes carbone et indicateurs (Module 4 — Sahar).
     * Lancer les seeders des modules 1 à 3 avant : les indicateurs sont
     * rattachés aux étapes réelles des lots.
     */
    public function run(): void
    {
        $lots = Lot::with(['produit.categorie', 'etapes' => fn ($q) => $q->with('acteur')->orderBy('date_heure')])
            ->has('etapes')
            ->get();

        foreach ($lots as $index => $lot) {
            $co2 = self::CO2[$lot->produit?->nom] ?? null;

            if ($co2 === null) {
                continue;
            }

            $empreinte = EmpreinteCarbone::factory()->for($lot)->create([
                'co2_total' => $co2,
                'methode' => $index % 3 === 0 ? 'ACV' : 'Agribalyse',
                'date_calcul' => min($lot->etapes->last()->date_heure->copy()->addDay()->startOfDay(), today()),
            ]);

            $precedent = null;

            foreach ($lot->etapes as $etape) {
                [$type, $valeur] = match ($etape->type_etape) {
                    'production' => ['eau', $lot->quantite * (self::EAU[$lot->produit->categorie?->type] ?? 1)],
                    'transformation' => ['energie', $lot->quantite * 0.15],
                    default => ['transport', $precedent ? $this->distanceRoute($precedent->acteur, $etape->acteur) : 0],
                };

                if ($etape->type_etape === 'transformation' && $precedent) {
                    $this->indicateur($empreinte, $etape->id, 'transport', $this->distanceRoute($precedent->acteur, $etape->acteur));
                }

                if ($valeur > 0) {
                    $this->indicateur($empreinte, $etape->id, $type, $valeur);
                }

                $precedent = $etape;
            }

            $this->indicateur($empreinte, null, 'emballage', $lot->quantite * 0.04);
        }
    }

    private function indicateur(EmpreinteCarbone $empreinte, ?int $etapeId, string $type, float $valeur): void
    {
        Indicateur::factory()->for($empreinte)->create([
            'etape_id' => $etapeId,
            'type' => $type,
            'valeur' => round($valeur, 2),
            'unite' => Indicateur::UNITES[$type],
        ]);
    }

    /**
     * Distance à vol d'oiseau (Haversine) majorée de 30 % pour approcher la route.
     */
    private function distanceRoute(?Acteur $depart, ?Acteur $arrivee): float
    {
        if (! $depart?->hasCoordinates() || ! $arrivee?->hasCoordinates()) {
            return 0;
        }

        $lat1 = deg2rad($depart->latitude);
        $lat2 = deg2rad($arrivee->latitude);
        $dLat = $lat2 - $lat1;
        $dLon = deg2rad($arrivee->longitude - $depart->longitude);

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

        return 6371 * 2 * asin(sqrt($a)) * 1.3;
    }
}
