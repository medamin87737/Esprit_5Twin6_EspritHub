<?php

namespace App\Support;

use App\Models\EmpreinteCarbone;
use App\Models\Indicateur;
use App\Models\Lot;
use App\Services\ClimatiqService;
use Illuminate\Support\Collection;

class CalculEmpreinte
{
    /**
     * kg CO₂e par unité de produit pour un indicateur : valeur × facteur ÷ quantité du lot.
     */
    public static function contributionCo2(Indicateur $indicateur, Lot $lot): float
    {
        $facteur = app(ClimatiqService::class)->obtenirFacteur($indicateur->type);

        return $indicateur->valeur * $facteur / max($lot->quantite, 1);
    }

    /**
     * @param  Collection<int, Indicateur>|iterable<int, Indicateur>  $indicateurs
     */
    public static function co2TotalPourLot(Lot $lot, iterable $indicateurs): float
    {
        return round(collect($indicateurs)->sum(fn (Indicateur $i) => self::contributionCo2($i, $lot)), 2);
    }

    /**
     * Recalcule le CO₂ par unité et le score A–E à partir de tous les indicateurs liés.
     */
    public static function synchroniser(EmpreinteCarbone $empreinte): EmpreinteCarbone
    {
        $empreinte->loadMissing(['lot', 'indicateurs']);

        if ($empreinte->lot === null) {
            return $empreinte;
        }

        $empreinte->co2_total = self::co2TotalPourLot($empreinte->lot, $empreinte->indicateurs);
        $empreinte->score = EmpreinteCarbone::scorePour($empreinte->co2_total);
        $empreinte->date_calcul = today();
        $empreinte->save();

        return $empreinte;
    }
}
