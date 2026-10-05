<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyseRequest;
use App\Models\Analyse;
use App\Models\Laboratoire;
use App\Models\Lot;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Analyses qualité déclarées par l'acteur qui détient le lot.
 */
class AnalyseController extends Controller
{
    public function create(Lot $lot): View
    {
        return view('pages.pro.analyses.form', [
            'lot' => $lot,
            'analyse' => new Analyse(['numero' => Analyse::prochainNumero(), 'date_prelevement' => today()]),
            'laboratoires' => $this->laboratoires(),
        ]);
    }

    public function store(AnalyseRequest $request, Lot $lot): RedirectResponse
    {
        $analyse = new Analyse($request->donnees());
        $analyse->lot()->associate($lot);
        $analyse->declarant()->associate($request->user());
        $analyse->remplacerRapport($request->file('rapport'));
        $analyse->save();

        return redirect()->route('pro.lots.show', $lot)
            ->with('status', "Analyse {$analyse->numero} enregistrée : {$analyse->resultatLabel()}.");
    }

    public function edit(Analyse $analyse): View
    {
        return view('pages.pro.analyses.form', [
            'lot' => $analyse->lot,
            'analyse' => $analyse,
            'laboratoires' => $this->laboratoires(),
        ]);
    }

    public function update(AnalyseRequest $request, Analyse $analyse): RedirectResponse
    {
        $analyse->fill($request->donnees());
        $analyse->remplacerRapport($request->file('rapport'));
        $analyse->save();

        return redirect()->route('pro.lots.show', $analyse->lot)
            ->with('status', "Analyse {$analyse->numero} mise à jour : {$analyse->resultatLabel()}.");
    }

    public function destroy(Analyse $analyse): RedirectResponse
    {
        $lot = $analyse->lot;
        $analyse->supprimerRapport();
        $analyse->delete();

        return redirect()->route('pro.lots.show', $lot)->with('status', "L'analyse {$analyse->numero} a été supprimée.");
    }

    /**
     * @return array<int, string>
     */
    private function laboratoires(): array
    {
        return Laboratoire::orderBy('nom')->get(['id', 'nom', 'ville'])
            ->mapWithKeys(fn (Laboratoire $l) => [$l->id => $l->nom.' ('.$l->ville.')'])
            ->all();
    }
}
