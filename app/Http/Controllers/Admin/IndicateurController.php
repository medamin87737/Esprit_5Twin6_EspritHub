<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndicateurRequest;
use App\Models\EmpreinteCarbone;
use App\Models\Etape;
use App\Models\Indicateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicateurController extends Controller
{
    public function index(Request $request): View
    {
        $indicateurs = Indicateur::query()
            ->with(['empreinteCarbone.lot.produit', 'etape'])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('empreinteCarbone.lot', fn ($l) => $l
                ->where('numero_lot', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.indicateurs.index', ['indicateurs' => $indicateurs]);
    }

    public function create(): View
    {
        return view('pages.admin.indicateurs.create', $this->formData());
    }

    public function store(IndicateurRequest $request): RedirectResponse
    {
        $indicateur = Indicateur::create($request->validated());

        return redirect()->route('admin.empreintes.show', $indicateur->empreinte_carbone_id)
            ->with('success', "L'indicateur « {$indicateur->typeLabel()} » a été ajouté.");
    }

    public function show(Indicateur $indicateur): View
    {
        $indicateur->load(['empreinteCarbone.lot.produit', 'etape.acteur']);

        return view('pages.admin.indicateurs.show', ['indicateur' => $indicateur]);
    }

    public function edit(Indicateur $indicateur): View
    {
        return view('pages.admin.indicateurs.edit', ['indicateur' => $indicateur] + $this->formData());
    }

    public function update(IndicateurRequest $request, Indicateur $indicateur): RedirectResponse
    {
        $indicateur->update($request->validated());

        return redirect()->route('admin.empreintes.show', $indicateur->empreinte_carbone_id)
            ->with('success', "L'indicateur « {$indicateur->typeLabel()} » a été mis à jour.");
    }

    public function destroy(Indicateur $indicateur): RedirectResponse
    {
        $indicateur->delete();

        return back()->with('success', "L'indicateur « {$indicateur->typeLabel()} » a été supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'empreintes' => EmpreinteCarbone::with('lot.produit:id,nom')->latest('date_calcul')->get(),
            'etapes' => Etape::with('lot:id,numero_lot')->has('lot.empreinteCarbone')->orderBy('lot_id')->orderBy('date_heure')->get(),
        ];
    }
}
