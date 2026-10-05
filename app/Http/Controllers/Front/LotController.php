<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LotController extends Controller
{
    public function show(Request $request, Lot $lot): View
    {
        $lot->load([
            'produit.categorie',
            'produit.certifications' => fn ($q) => $q->valides()->with('organisme:id,nom'),
            'acteurCourant',
            'empreinteCarbone.indicateurs',
            'etapes' => fn ($q) => $q->with(['acteur.typeActeur', 'indicateurs'])->orderBy('date_heure'),
            'analyses' => fn ($q) => $q->with('laboratoire:id,nom,accreditation')->latest('date_prelevement'),
        ]);

        $this->enregistrerScan($request, $lot);

        $points = $lot->etapes
            ->filter(fn ($etape) => $etape->acteur?->hasCoordinates())
            ->map(fn ($etape) => [
                'lat' => $etape->acteur->latitude,
                'lng' => $etape->acteur->longitude,
                'titre' => $etape->typeLabel().' · '.$etape->acteur->nom,
                'texte' => $etape->lieu.' · '.$etape->date_heure?->format('d/m/Y'),
            ])
            ->values()
            ->all();

        $totaux = $lot->empreinteCarbone?->indicateurs
            ->groupBy('type')
            ->map(fn ($groupe) => ['valeur' => $groupe->sum('valeur'), 'unite' => $groupe->first()->unite, 'indicateur' => $groupe->first()]);

        return view('pages.front.lots.show', [
            'lot' => $lot,
            'points' => $points,
            'totaux' => $totaux ?? collect(),
        ]);
    }

    /**
     * Historique des scans du consommateur connecté (une entrée par lot et par tranche de 10 minutes).
     */
    private function enregistrerScan(Request $request, Lot $lot): void
    {
        $user = $request->user();

        if (! $user?->isConsommateur()) {
            return;
        }

        $dejaVu = $user->scans()->where('lot_id', $lot->id)->where('created_at', '>=', now()->subMinutes(10))->exists();

        if (! $dejaVu) {
            $user->scans()->create(['lot_id' => $lot->id]);
        }
    }
}
