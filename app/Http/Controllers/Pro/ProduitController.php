<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProduitRequest;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Produits du producteur ou du transformateur connecté (ProduitPolicy : uniquement les siens).
 */
class ProduitController extends Controller
{
    public function index(Request $request): View
    {
        $produits = $request->user()->produits()
            ->with('categorie')
            ->withCount('lots')
            ->avecScore()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', '%'.$request->input('q').'%')
                ->orWhere('code_barres', 'like', '%'.$request->input('q').'%')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.pro.produits.index', ['produits' => $produits]);
    }

    public function create(): View
    {
        Gate::authorize('create', Produit::class);

        return view('pages.pro.produits.form', [
            'produit' => new Produit,
            'categories' => Categorie::orderBy('nom')->pluck('nom', 'id'),
        ]);
    }

    public function store(ProduitRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['proprietaire_id'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('produits', 'public');
        }

        $produit = Produit::create($data);
        $request->user()->acteur?->produits()->syncWithoutDetaching([$produit->id]);

        return redirect()->route('pro.produits.index')->with('status', "Le produit « {$produit->nom} » a été ajouté au catalogue.");
    }

    public function edit(Produit $produit): View
    {
        Gate::authorize('update', $produit);

        return view('pages.pro.produits.form', [
            'produit' => $produit,
            'categories' => Categorie::orderBy('nom')->pluck('nom', 'id'),
        ]);
    }

    public function update(ProduitRequest $request, Produit $produit): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($produit->image) {
                Storage::disk('public')->delete($produit->image);
            }
            $data['image'] = $request->file('image')->store('produits', 'public');
        } else {
            unset($data['image']);
        }

        $produit->update($data);

        return redirect()->route('pro.produits.index')->with('status', "Le produit « {$produit->nom} » a été mis à jour.");
    }

    public function destroy(Produit $produit): RedirectResponse
    {
        Gate::authorize('delete', $produit);

        if ($produit->lots()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$produit->nom} » : des lots de ce produit sont déjà tracés.");
        }

        if ($produit->image) {
            Storage::disk('public')->delete($produit->image);
        }

        $produit->delete();

        return redirect()->route('pro.produits.index')->with('status', "Le produit « {$produit->nom} » a été supprimé.");
    }
}
