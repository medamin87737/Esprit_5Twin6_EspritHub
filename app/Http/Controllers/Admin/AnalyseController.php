<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyseRequest;
use App\Models\Analyse;
use App\Models\Laboratoire;
use App\Models\Lot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyseController extends Controller
{
    public function index(Request $request): View
    {
        $analyses = Analyse::query()
            ->with(['lot:id,numero_lot,produit_id', 'lot.produit:id,nom', 'laboratoire:id,nom'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('numero', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('lot', fn ($l) => $l->where('numero_lot', 'like', '%'.$request->input('q').'%'))
                ->orWhereHas('lot.produit', fn ($p) => $p->where('nom', 'like', '%'.$request->input('q').'%'))))
            ->when($request->filled('resultat'), fn ($q) => $q->resultat($request->input('resultat')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('laboratoire'), fn ($q) => $q->where('laboratoire_id', $request->input('laboratoire')))
            ->when($request->filled('lot'), fn ($q) => $q->where('lot_id', $request->input('lot')))
            ->latest('date_prelevement')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.analyses.index', [
            'analyses' => $analyses,
            'laboratoires' => Laboratoire::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.analyses.create', $this->formData());
    }

    public function store(AnalyseRequest $request): RedirectResponse
    {
        $analyse = new Analyse($request->donnees());
        $analyse->declarant()->associate($request->user());
        $analyse->remplacerRapport($request->file('rapport'));
        $analyse->save();

        return redirect()->route('admin.analyses.show', $analyse)
            ->with('success', "L'analyse {$analyse->numero} a été enregistrée pour le lot {$analyse->lot->numero_lot}.");
    }

    public function show(Analyse $analyse): View
    {
        $analyse->load(['laboratoire', 'lot.produit.categorie', 'lot.acteurCourant', 'declarant:id,name,role']);

        return view('pages.admin.analyses.show', [
            'analyse' => $analyse,
            'autresAnalyses' => $analyse->lot->analyses()
                ->with('laboratoire:id,nom')
                ->whereKeyNot($analyse->id)
                ->latest('date_prelevement')
                ->get(),
        ]);
    }

    public function edit(Analyse $analyse): View
    {
        return view('pages.admin.analyses.edit', ['analyse' => $analyse] + $this->formData());
    }

    public function update(AnalyseRequest $request, Analyse $analyse): RedirectResponse
    {
        $analyse->fill($request->donnees());
        $analyse->remplacerRapport($request->file('rapport'));
        $analyse->save();

        return redirect()->route('admin.analyses.index')
            ->with('success', "L'analyse {$analyse->numero} a été mise à jour.");
    }

    public function destroy(Analyse $analyse): RedirectResponse
    {
        $analyse->supprimerRapport();
        $analyse->delete();

        return redirect()->route('admin.analyses.index')
            ->with('success', "L'analyse {$analyse->numero} a été supprimée.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'laboratoires' => Laboratoire::orderBy('nom')->get(['id', 'nom', 'ville']),
            'lots' => Lot::with('produit:id,nom')->latest('date_production')->get(['id', 'numero_lot', 'produit_id']),
            'prochainNumero' => Analyse::prochainNumero(),
        ];
    }
}
