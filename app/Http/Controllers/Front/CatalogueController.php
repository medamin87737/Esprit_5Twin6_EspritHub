<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    public function __invoke(Request $request): View
    {
        $produits = Produit::query()
            ->with(['categorie', 'certifications' => fn ($q) => $q->valides()->select('id', 'produit_id', 'type', 'numero')])
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie_id', $request->input('categorie')))
            ->when($request->filled('label'), fn ($q) => $q->whereHas('certifications', fn ($c) => $c->valides()->where('type', $request->input('label'))))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('pages.front.produits.index', [
            'produits' => $produits,
            'categories' => Categorie::orderBy('nom')->get(),
        ]);
    }
}
