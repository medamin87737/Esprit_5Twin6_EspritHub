<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProduitRequest;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Partagé par l'espace administrateur (admin.produits.*) et l'espace
 * fournisseur (fournisseur.produits.*) : un fournisseur ne voit et ne
 * modifie que ses propres produits (voir ProduitPolicy).
 */
class ProduitController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Produit::class);

        $produits = Produit::query()
            ->with(['categorie', 'fournisseur:id,name'])
            ->when($this->estFournisseur(), fn ($q) => $q->where('fournisseur_id', $request->user()->id))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', '%'.$request->input('q').'%')
                ->orWhere('code_barres', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie_id', $request->input('categorie')))
            ->when(! $this->estFournisseur() && $request->filled('fournisseur'), fn ($q) => $q->where('fournisseur_id', $request->input('fournisseur')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.produits.index', [
            'produits' => $produits,
            'categories' => Categorie::orderBy('nom')->get(),
            'fournisseurs' => $this->estFournisseur() ? collect() : User::fournisseurs()->orderBy('name')->get(['id', 'name']),
        ] + $this->espace());
    }

    public function create(): View
    {
        Gate::authorize('create', Produit::class);

        return view('pages.admin.produits.create', $this->formData());
    }

    public function store(ProduitRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($this->estFournisseur()) {
            $data['fournisseur_id'] = $request->user()->id;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('produits', 'public');
        }

        $produit = Produit::create($data);

        return redirect()->route($this->prefixe().'.produits.index')
            ->with('success', "Le produit « {$produit->nom} » a été créé.");
    }

    public function show(Produit $produit): View
    {
        Gate::authorize('view', $produit);

        $produit->load([
            'categorie',
            'fournisseur',
            'acteurs.typeActeur',
            'certifications' => fn ($q) => $q->with('organisme:id,nom')->orderBy('type'),
            'lots' => fn ($q) => $q->with('empreinteCarbone:id,lot_id,score,co2_total')->withCount('etapes')->latest('date_production'),
        ])->loadCount('etapes')->loadAvg('empreintes', 'co2_total');

        return view('pages.admin.produits.show', ['produit' => $produit] + $this->espace());
    }

    public function edit(Produit $produit): View
    {
        Gate::authorize('update', $produit);

        return view('pages.admin.produits.edit', ['produit' => $produit] + $this->formData());
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

        return redirect()->route($this->prefixe().'.produits.index')
            ->with('success', "Le produit « {$produit->nom} » a été mis à jour.");
    }

    public function destroy(Produit $produit): RedirectResponse
    {
        Gate::authorize('delete', $produit);

        if ($produit->lots()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$produit->nom} » : des lots de ce produit sont enregistrés.");
        }

        if ($produit->image) {
            Storage::disk('public')->delete($produit->image);
        }

        $produit->delete();

        return redirect()->route($this->prefixe().'.produits.index')
            ->with('success', "Le produit « {$produit->nom} » a été supprimé.");
    }

    private function estFournisseur(): bool
    {
        return request()->routeIs('fournisseur.*');
    }

    private function prefixe(): string
    {
        return $this->estFournisseur() ? 'fournisseur' : 'admin';
    }

    /**
     * @return array<string, string>
     */
    private function espace(): array
    {
        return [
            'espace' => $this->prefixe(),
            'moduleLabel' => $this->estFournisseur() ? 'Espace fournisseur' : 'Module 1 · Produits & Catégories',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => Categorie::orderBy('nom')->get(),
            'fournisseurs' => $this->estFournisseur() ? collect() : User::fournisseurs()->orderBy('name')->get(['id', 'name']),
        ] + $this->espace();
    }
}
