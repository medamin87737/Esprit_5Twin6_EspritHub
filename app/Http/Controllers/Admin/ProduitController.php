<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProduitRequest;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProduitController extends Controller
{
    public function index(Request $request): View
    {
        $produits = Produit::query()
            ->with('categorie')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', '%'.$request->input('q').'%')
                ->orWhere('code_barres', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie_id', $request->input('categorie')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.produits.index', [
            'produits' => $produits,
            'categories' => Categorie::orderBy('nom')->get(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.produits.create', ['categories' => Categorie::orderBy('nom')->get()]);
    }

    public function store(ProduitRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('produits', 'public');
        }

        $produit = Produit::create($data);

        return redirect()->route('admin.produits.index')
            ->with('success', "Le produit « {$produit->nom} » a été créé.");
    }

    public function show(Produit $produit): View
    {
        $produit->load('categorie');

        return view('pages.admin.produits.show', ['produit' => $produit]);
    }

    public function edit(Produit $produit): View
    {
        return view('pages.admin.produits.edit', [
            'produit' => $produit,
            'categories' => Categorie::orderBy('nom')->get(),
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

        return redirect()->route('admin.produits.index')
            ->with('success', "Le produit « {$produit->nom} » a été mis à jour.");
    }

    public function destroy(Produit $produit): RedirectResponse
    {
        if ($produit->image) {
            Storage::disk('public')->delete($produit->image);
        }

        $produit->delete();

        return redirect()->route('admin.produits.index')
            ->with('success', "Le produit « {$produit->nom} » a été supprimé.");
    }
}
