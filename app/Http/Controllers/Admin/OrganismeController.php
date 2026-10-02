<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrganismeRequest;
use App\Models\Organisme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganismeController extends Controller
{
    public function index(Request $request): View
    {
        $organismes = Organisme::query()
            ->withCount(['certifications', 'certifications as certifications_valides_count' => fn ($q) => $q->valides()])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', '%'.$request->input('q').'%')
                ->orWhere('accreditation', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('pays'), fn ($q) => $q->where('pays', $request->input('pays')))
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.organismes.index', [
            'organismes' => $organismes,
            'pays' => Organisme::query()->distinct()->orderBy('pays')->pluck('pays'),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.organismes.create');
    }

    public function store(OrganismeRequest $request): RedirectResponse
    {
        $organisme = Organisme::create($request->validated());

        return redirect()->route('admin.organismes.show', $organisme)
            ->with('success', "L'organisme « {$organisme->nom} » a été créé. Vous pouvez maintenant lui attribuer des certifications.");
    }

    public function show(Organisme $organisme): View
    {
        $organisme->load(['certifications' => fn ($q) => $q->with('produit.categorie')->latest('date_obtention')]);

        return view('pages.admin.organismes.show', [
            'organisme' => $organisme,
            'nbProduits' => $organisme->produits()->distinct()->count('produits.id'),
        ]);
    }

    public function edit(Organisme $organisme): View
    {
        return view('pages.admin.organismes.edit', ['organisme' => $organisme]);
    }

    public function update(OrganismeRequest $request, Organisme $organisme): RedirectResponse
    {
        $organisme->update($request->validated());

        return redirect()->route('admin.organismes.index')
            ->with('success', "L'organisme « {$organisme->nom} » a été mis à jour.");
    }

    public function destroy(Organisme $organisme): RedirectResponse
    {
        if (($nb = $organisme->certifications()->count()) > 0) {
            return back()->with('error', "Impossible de supprimer « {$organisme->nom} » : il a délivré {$nb} certification(s). Supprimez-les ou réattribuez-les d'abord.");
        }

        $organisme->delete();

        return redirect()->route('admin.organismes.index')
            ->with('success', "L'organisme « {$organisme->nom} » a été supprimé.");
    }
}
