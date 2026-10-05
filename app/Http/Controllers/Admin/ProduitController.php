<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProduitRequest;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProduitController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Produit::class);

        $produits = Produit::query()
            ->with(['categorie', 'proprietaire:id,name,role'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', '%'.$request->input('q').'%')
                ->orWhere('code_barres', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie_id', $request->input('categorie')))
            ->when($request->filled('proprietaire'), fn ($q) => $q->where('proprietaire_id', $request->input('proprietaire')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.produits.index', [
            'produits' => $produits,
            'categories' => Categorie::orderBy('nom')->get(),
            'proprietaires' => $this->proprietaires(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Produit::class);

        return view('pages.admin.produits.create', $this->formData());
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
        Gate::authorize('view', $produit);

        $produit->load([
            'categorie',
            'proprietaire',
            'acteurs.typeActeur',
            'certifications' => fn ($q) => $q->with('organisme:id,nom')->orderBy('type'),
            'lots' => fn ($q) => $q->with('empreinteCarbone:id,lot_id,score,co2_total')->withCount('etapes')->latest('date_production'),
        ])->loadCount('etapes')->loadAvg('empreintes', 'co2_total');

        return view('pages.admin.produits.show', ['produit' => $produit]);
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

        return redirect()->route('admin.produits.index')
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

        return redirect()->route('admin.produits.index')
            ->with('success', "Le produit « {$produit->nom} » a été supprimé.");
    }

    /**
     * @return Collection<int, User>
     */
    private function proprietaires(): Collection
    {
        return User::gestionnairesProduits()->orderBy('name')->get(['id', 'name', 'role']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => Categorie::orderBy('nom')->get(),
            'proprietaires' => $this->proprietaires(),
        ];
    }
}
