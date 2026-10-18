<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use App\Models\Lot;
use App\Services\Analyses\AnalyseSynthese;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hub Qualité pro : lots détenus + accès aux fonctions avancées (IA, risque, synthèse).
 */
class QualiteController extends Controller
{
    public function __construct(private AnalyseSynthese $synthese) {}

    public function __invoke(Request $request): View
    {
        $acteur = $request->user()->acteur;

        $lots = Lot::query()
            ->with([
                'produit:id,nom',
                'analyses' => fn ($q) => $q->latest()->limit(5),
            ])
            ->withCount('analyses')
            ->where('acteur_courant_id', $acteur?->id)
            ->latest()
            ->paginate(12);

        $lots->getCollection()->transform(function (Lot $lot) {
            $lot->setAttribute('synthese', $this->synthese->pourLot($lot));

            return $lot;
        });

        $mesLotsIds = Lot::query()->where('acteur_courant_id', $acteur?->id)->select('id');

        $stats = [
            'lots' => (clone $mesLotsIds)->count(),
            'analyses' => Analyse::query()->whereIn('lot_id', $mesLotsIds)->count(),
            'non_conformes' => Analyse::query()->whereIn('lot_id', $mesLotsIds)->resultat('non_conforme')->count(),
            'en_attente' => Analyse::query()->whereIn('lot_id', $mesLotsIds)->resultat('en_attente')->count(),
            'en_retard' => Analyse::query()
                ->whereIn('lot_id', $mesLotsIds)
                ->resultat('en_attente')
                ->whereDate('date_prelevement', '<=', today()->subDays((int) config('openrouter.alerte_en_attente_jours', 7)))
                ->count(),
        ];

        return view('pages.pro.qualite.index', [
            'lots' => $lots,
            'stats' => $stats,
            'role' => $request->user()->role,
        ]);
    }
}
