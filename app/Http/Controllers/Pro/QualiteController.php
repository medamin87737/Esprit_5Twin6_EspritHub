<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hub Qualité pro : choisir un lot détenu pour y déclarer une analyse.
 */
class QualiteController extends Controller
{
    public function __invoke(Request $request): View
    {
        $acteur = $request->user()->acteur;

        $lots = Lot::query()
            ->with(['produit:id,nom', 'analyses' => fn ($q) => $q->latest()->limit(3)])
            ->withCount('analyses')
            ->where('acteur_courant_id', $acteur?->id)
            ->latest()
            ->paginate(12);

        return view('pages.pro.qualite.index', [
            'lots' => $lots,
            'role' => $request->user()->role,
        ]);
    }
}
