<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LaboratoireRequest;
use App\Models\Laboratoire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaboratoireController extends Controller
{
    public function index(Request $request): View
    {
        $laboratoires = Laboratoire::query()
            ->withCount(['analyses', 'analyses as non_conformes_count' => fn ($q) => $q->resultat('non_conforme')])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', '%'.$request->input('q').'%')
                ->orWhere('accreditation', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('ville'), fn ($q) => $q->where('ville', $request->input('ville')))
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.laboratoires.index', [
            'laboratoires' => $laboratoires,
            'villes' => Laboratoire::query()->distinct()->orderBy('ville')->pluck('ville'),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.laboratoires.create');
    }

    public function store(LaboratoireRequest $request): RedirectResponse
    {
        $laboratoire = Laboratoire::create($request->validated());

        return redirect()->route('admin.laboratoires.show', $laboratoire)
            ->with('success', "Le laboratoire « {$laboratoire->nom} » a été créé. Vous pouvez maintenant lui confier des analyses.");
    }

    public function show(Laboratoire $laboratoire): View
    {
        $laboratoire->load(['analyses' => fn ($q) => $q->with('lot.produit:id,nom')->latest('date_prelevement')]);

        return view('pages.admin.laboratoires.show', [
            'laboratoire' => $laboratoire,
            'nbLots' => $laboratoire->lots()->distinct()->count('lots.id'),
        ]);
    }

    public function edit(Laboratoire $laboratoire): View
    {
        return view('pages.admin.laboratoires.edit', ['laboratoire' => $laboratoire]);
    }

    public function update(LaboratoireRequest $request, Laboratoire $laboratoire): RedirectResponse
    {
        $laboratoire->update($request->validated());

        return redirect()->route('admin.laboratoires.index')
            ->with('success', "Le laboratoire « {$laboratoire->nom} » a été mis à jour.");
    }

    public function destroy(Laboratoire $laboratoire): RedirectResponse
    {
        if (($nb = $laboratoire->analyses()->count()) > 0) {
            return back()->with('error', "Impossible de supprimer « {$laboratoire->nom} » : il a réalisé {$nb} analyse(s). Supprimez-les ou confiez-les à un autre laboratoire d'abord.");
        }

        $laboratoire->delete();

        return redirect()->route('admin.laboratoires.index')
            ->with('success', "Le laboratoire « {$laboratoire->nom} » a été supprimé.");
    }
}
