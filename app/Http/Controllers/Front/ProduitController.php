<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\View\View;

class ProduitController extends Controller
{
    public function show(Produit $produit): View
    {
        $produit->load([
            'categorie',
            'certifications' => fn ($q) => $q->valides()->with('organisme:id,nom')->orderBy('type'),
            'lots' => fn ($q) => $q->with(['empreinteCarbone:id,lot_id,score,co2_total', 'analyses:id,lot_id,resultat'])->withCount('etapes')->latest('date_production'),
            'signalements' => fn ($q) => $q->valides()->with('user:id,name')->latest(),
        ])->loadAvg('empreintes', 'co2_total');

        return view('pages.front.produits.show', ['produit' => $produit]);
    }
}
