<?php

namespace App\Http\Controllers\Consommateur;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use App\Models\Produit;
use Illuminate\View\View;

/**
 * Module 2 (Amin) — résumé qualité d'un produit, chargé en AJAX
 * dans le popup « Qualité » des cartes catalogue (pas de changement de page).
 */
class ProduitQualiteController extends Controller
{
    public function __invoke(Produit $produit): View
    {
        $produit->loadMissing('categorie:id,nom');

        $analyses = Analyse::query()
            ->whereHas('lot', fn ($q) => $q->where('produit_id', $produit->id));

        $total = (clone $analyses)->count();
        $conformes = (clone $analyses)->where('resultat', 'conforme')->count();
        $nonConformes = (clone $analyses)->where('resultat', 'non_conforme')->count();
        $enAttente = (clone $analyses)->where('resultat', 'en_attente')->count();

        $decidees = $conformes + $nonConformes;
        $pctConformes = $decidees > 0 ? (int) round($conformes * 100 / $decidees) : null;

        $statut = match (true) {
            $total === 0 => null,
            $nonConformes > 0 => 'non_conforme',
            $conformes > 0 && $enAttente === 0 => 'conforme',
            $conformes > 0 => 'conforme',
            default => 'en_attente',
        };

        return view('pages.front.consommateur._qualite-popup', [
            'produit' => $produit,
            'score' => $produit->scoreGlobal(),
            'statut' => $statut,
            'total' => $total,
            'conformes' => $conformes,
            'nonConformes' => $nonConformes,
            'enAttente' => $enAttente,
            'pctConformes' => $pctConformes,
            'nbLots' => $produit->lots()->count(),
            'nbLotsControles' => $produit->lots()->whereHas('analyses')->count(),
            // Lot ouvert dans l'écran Qualité complet : le plus récent ayant des analyses,
            // sinon le plus récent tout court.
            'lotPrincipal' => $produit->lots()->whereHas('analyses')->latest('date_production')->first()
                ?? $produit->lots()->latest('date_production')->first(),
            'dernieres' => (clone $analyses)
                ->with(['laboratoire:id,nom', 'lot:id,numero_lot'])
                ->latest('date_prelevement')
                ->limit(5)
                ->get(),
        ]);
    }
}
