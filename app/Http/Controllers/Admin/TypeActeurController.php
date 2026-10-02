<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TypeActeurRequest;
use App\Models\TypeActeur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TypeActeurController extends Controller
{
    public function index(Request $request): View
    {
        $typeActeurs = TypeActeur::query()
            ->withCount('acteurs')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('libelle', 'like', '%'.$request->input('q').'%')
                ->orWhere('role_chaine', 'like', '%'.$request->input('q').'%')))
            ->orderBy('libelle')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.type-acteurs.index', ['typeActeurs' => $typeActeurs]);
    }

    public function create(): View
    {
        return view('pages.admin.type-acteurs.create');
    }

    public function store(TypeActeurRequest $request): RedirectResponse
    {
        $typeActeur = TypeActeur::create($request->validated());

        return redirect()->route('admin.type-acteurs.index')
            ->with('success', "Le type « {$typeActeur->libelle} » a été créé.");
    }

    public function show(TypeActeur $typeActeur): View
    {
        $typeActeur->load(['acteurs' => fn ($q) => $q->withCount('produits')->orderBy('nom')]);

        return view('pages.admin.type-acteurs.show', ['typeActeur' => $typeActeur]);
    }

    public function edit(TypeActeur $typeActeur): View
    {
        return view('pages.admin.type-acteurs.edit', ['typeActeur' => $typeActeur]);
    }

    public function update(TypeActeurRequest $request, TypeActeur $typeActeur): RedirectResponse
    {
        $typeActeur->update($request->validated());

        return redirect()->route('admin.type-acteurs.index')
            ->with('success', "Le type « {$typeActeur->libelle} » a été mis à jour.");
    }

    public function destroy(TypeActeur $typeActeur): RedirectResponse
    {
        if ($typeActeur->acteurs()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$typeActeur->libelle} » : des acteurs y sont encore rattachés.");
        }

        $typeActeur->delete();

        return redirect()->route('admin.type-acteurs.index')
            ->with('success', "Le type « {$typeActeur->libelle} » a été supprimé.");
    }
}
