<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategorieRequest;
use App\Models\Categorie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategorieController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Categorie::query()
            ->withCount('produits')
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('pages.admin.categories.create');
    }

    public function store(CategorieRequest $request): RedirectResponse
    {
        $categorie = Categorie::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', "La catégorie « {$categorie->nom} » a été créée.");
    }

    public function show(Categorie $categorie): View
    {
        $categorie->load(['produits' => fn ($q) => $q->latest()]);

        return view('pages.admin.categories.show', ['categorie' => $categorie]);
    }

    public function edit(Categorie $categorie): View
    {
        return view('pages.admin.categories.edit', ['categorie' => $categorie]);
    }

    public function update(CategorieRequest $request, Categorie $categorie): RedirectResponse
    {
        $categorie->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', "La catégorie « {$categorie->nom} » a été mise à jour.");
    }

    public function destroy(Categorie $categorie): RedirectResponse
    {
        if ($categorie->produits()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$categorie->nom} » : des produits y sont encore rattachés.");
        }

        $categorie->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "La catégorie « {$categorie->nom} » a été supprimée.");
    }
}
