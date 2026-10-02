<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActeurRequest;
use App\Models\Acteur;
use App\Models\Categorie;
use App\Models\TypeActeur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActeurController extends Controller
{
    public function index(Request $request): View
    {
        $acteurs = Acteur::query()
            ->with('typeActeur')
            ->withCount('produits')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', '%'.$request->input('q').'%')
                ->orWhere('pays', 'like', '%'.$request->input('q').'%')
                ->orWhere('email', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('type'), fn ($q) => $q->where('type_acteur_id', $request->input('type')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.acteurs.index', [
            'acteurs' => $acteurs,
            'typeActeurs' => TypeActeur::orderBy('libelle')->get(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.acteurs.create', $this->formData());
    }

    public function store(ActeurRequest $request): RedirectResponse
    {
        $acteur = DB::transaction(function () use ($request) {
            $acteur = Acteur::create($request->safe()->except('produits'));
            $acteur->produits()->sync($request->validated('produits', []));

            return $acteur;
        });

        return redirect()->route('admin.acteurs.index')
            ->with('success', "L'acteur « {$acteur->nom} » a été créé.");
    }

    public function show(Acteur $acteur): View
    {
        $acteur->load([
            'typeActeur',
            'produits.categorie',
            'etapes' => fn ($q) => $q->with('lot.produit')->latest('date_heure'),
        ]);

        return view('pages.admin.acteurs.show', ['acteur' => $acteur]);
    }

    public function edit(Acteur $acteur): View
    {
        $acteur->load('produits:id');

        return view('pages.admin.acteurs.edit', ['acteur' => $acteur] + $this->formData());
    }

    public function update(ActeurRequest $request, Acteur $acteur): RedirectResponse
    {
        DB::transaction(function () use ($request, $acteur) {
            $acteur->update($request->safe()->except('produits'));
            $acteur->produits()->sync($request->validated('produits', []));
        });

        return redirect()->route('admin.acteurs.index')
            ->with('success', "L'acteur « {$acteur->nom} » a été mis à jour.");
    }

    public function destroy(Acteur $acteur): RedirectResponse
    {
        if ($acteur->etapes()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$acteur->nom} » : il intervient dans des étapes de traçabilité.");
        }

        $acteur->delete();

        return redirect()->route('admin.acteurs.index')
            ->with('success', "L'acteur « {$acteur->nom} » a été supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'typeActeurs' => TypeActeur::orderBy('libelle')->get(),
            'categories' => Categorie::with(['produits' => fn ($q) => $q->orderBy('nom')])->orderBy('nom')->get(),
        ];
    }
}
