<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Module 1 — Fiche produit publique (Ghada).
 */
class ProduitController extends Controller
{
    public function show(Produit $produit): View
    {
        $produit->load([
            'categorie',
            'certifications' => fn ($q) => $q->valides()->with('organisme:id,nom')->orderBy('type'),
            'lots' => fn ($q) => $q
                ->with(['empreinteCarbone:id,lot_id,score,co2_total', 'analyses:id,lot_id,resultat'])
                ->withCount('etapes')
                ->latest('date_production'),
            'signalements' => fn ($q) => $q->valides()->with('user:id,name')->latest(),
        ])->loadAvg('empreintes', 'co2_total');

        // ── KPIs ─────────────────────────────────────────────────────────────
        $lots = $produit->lots;
        $lotsAvecScore = $lots->filter(fn ($l) => $l->empreinteCarbone !== null);

        $lotsAnalyses = $lots->filter(fn ($l) => $l->analyses->isNotEmpty());
        $lotsConformes = $lotsAnalyses->filter(
            fn ($l) => $l->analyses->every(fn ($a) => $a->resultat === 'conforme')
        );
        $pctConformes = $lotsAnalyses->isNotEmpty()
            ? round($lotsConformes->count() / $lotsAnalyses->count() * 100)
            : null;

        $nbEtapes = $lots->sum('etapes_count');

        return view('pages.front.produits.show', [
            'produit' => $produit,
            'nbEtapes' => $nbEtapes,
            'pctConformes' => $pctConformes,
            'lotsAvecScore' => $lotsAvecScore,
        ]);
    }

    /**
     * JSON pour les onglets lots (A3).
     * scopeBindings garantit que $lot appartient bien à $produit.
     */
    public function lotJson(Produit $produit, Lot $lot): JsonResponse
    {
        // scopeBindings fait le contrôle : si $lot n'appartient pas à $produit → 404
        // On recharge avec tous les détails (load, pas loadMissing)
        $lot->load([
            'acteurCourant',
            'empreinteCarbone.indicateurs',
            'etapes' => fn ($q) => $q->with('acteur:id,nom')->orderBy('date_heure'),
            'analyses' => fn ($q) => $q->with('laboratoire:id,nom')->orderBy('date_resultat'),
        ]);

        $facteurs = config('nutritrace.facteurs_co2', []);
        $empreinte = $lot->empreinteCarbone;
        $co2Total = $empreinte?->co2_total ?? 0;

        // Part de chaque indicateur dans le CO₂ total du lot
        $parts = [];
        if ($empreinte && $co2Total > 0 && $lot->quantite > 0) {
            foreach (['eau', 'energie', 'transport', 'emballage'] as $type) {
                $sommeValeurs = $empreinte->indicateurs
                    ->where('type', $type)
                    ->sum('valeur');
                $contrib = $sommeValeurs * ($facteurs[$type] ?? 0) / $lot->quantite;
                $parts[$type] = ['valeur' => round($contrib, 3), 'pct' => 0];
            }
            // Calcule les pourcentages
            $sommeParts = array_sum(array_column($parts, 'valeur'));
            if ($sommeParts > 0) {
                foreach ($parts as $type => &$part) {
                    $part['pct'] = round($part['valeur'] / $sommeParts * 100);
                }
                unset($part);
            }
        }

        $html = view('pages.front.produits._lot_detail', [
            'lot' => $lot,
            'produit' => $produit,
            'empreinte' => $empreinte,
            'parts' => $parts,
            'facteurs' => $facteurs,
        ])->render();

        return response()->json(['html' => $html]);
    }
}
