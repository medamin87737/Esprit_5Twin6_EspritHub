<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TracabiliteController extends Controller
{
    public function __invoke(Request $request): View
    {
        $numero = strtoupper(trim((string) $request->query('numero')));

        $lot = $numero === '' ? null : Lot::query()
            ->with([
                'produit.categorie',
                'empreinteCarbone',
                'etapes' => fn ($q) => $q->with('acteur.typeActeur')->orderBy('date_heure'),
            ])
            ->where('numero_lot', $numero)
            ->first();

        return view('pages.front.lots.search', [
            'lot' => $lot,
            'exemples' => Lot::has('etapes')->latest('date_production')->limit(3)->pluck('numero_lot'),
        ]);
    }
}
