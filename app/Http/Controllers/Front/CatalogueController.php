<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categorie' => ['nullable', 'integer'],
            'score' => ['nullable', Rule::in(array_keys(config('nutritrace.options.scores')))],
            'label' => ['nullable', Rule::in(array_keys(config('nutritrace.options.certification_types')))],
        ]);

        $produits = Produit::query()
            ->with(['categorie', 'certifications' => fn ($q) => $q->valides()->with('organisme:id,nom')])
            ->avecScore()
            ->when($filtres['q'] ?? null, fn ($q, $terme) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', "%{$terme}%")
                ->orWhere('code_barres', 'like', "%{$terme}%")))
            ->when($filtres['categorie'] ?? null, fn ($q, $categorie) => $q->where('categorie_id', $categorie))
            ->when($filtres['score'] ?? null, fn ($q, $score) => $q->score($score))
            ->when($filtres['label'] ?? null, fn ($q, $label) => $q->whereHas('certifications', fn ($c) => $c->valides()->where('type', $label)))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('pages.front.produits.index', [
            'produits' => $produits,
            'categories' => Categorie::orderBy('nom')->get(),
        ]);
    }
}
