<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmpreinteCarboneRequest;
use App\Models\EmpreinteCarbone;
use App\Models\Lot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmpreinteCarboneController extends Controller
{
    public function index(Request $request): View
    {
        $empreintes = EmpreinteCarbone::query()
            ->with('lot.produit')
            ->withCount('indicateurs')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('methode', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('lot', fn ($l) => $l->where('numero_lot', 'like', '%'.$request->input('q').'%')
                    ->orWhereHas('produit', fn ($p) => $p->where('nom', 'like', '%'.$request->input('q').'%')))))
            ->when($request->filled('score'), fn ($q) => $q->where('score', $request->input('score')))
            ->latest('date_calcul')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.empreintes.index', ['empreintes' => $empreintes]);
    }

    public function create(): View
    {
        return view('pages.admin.empreintes.create', ['lots' => $this->lotsDisponibles()]);
    }

    public function store(EmpreinteCarboneRequest $request): RedirectResponse
    {
        $empreinte = EmpreinteCarbone::create($request->validated());

        return redirect()->route('admin.empreintes.show', $empreinte)
            ->with('success', "Empreinte enregistrée : éco-score {$empreinte->score}. Détaillez-la maintenant avec ses indicateurs.");
    }

    public function show(EmpreinteCarbone $empreinte): View
    {
        $empreinte->load([
            'lot.produit.categorie',
            'lot.etapes' => fn ($q) => $q->orderBy('date_heure'),
            'indicateurs' => fn ($q) => $q->with('etape')->orderBy('type'),
        ]);

        return view('pages.admin.empreintes.show', ['empreinte' => $empreinte]);
    }

    public function edit(EmpreinteCarbone $empreinte): View
    {
        return view('pages.admin.empreintes.edit', [
            'empreinte' => $empreinte,
            'lots' => $this->lotsDisponibles($empreinte),
        ]);
    }

    public function update(EmpreinteCarboneRequest $request, EmpreinteCarbone $empreinte): RedirectResponse
    {
        $empreinte->update($request->validated());

        return redirect()->route('admin.empreintes.index')
            ->with('success', "L'empreinte du lot « {$empreinte->lot->numero_lot} » a été mise à jour (score {$empreinte->score}).");
    }

    public function destroy(EmpreinteCarbone $empreinte): RedirectResponse
    {
        $numero = $empreinte->lot?->numero_lot;
        $indicateurs = $empreinte->indicateurs()->count();
        $empreinte->delete();

        return redirect()->route('admin.empreintes.index')
            ->with('success', "L'empreinte du lot « {$numero} » et ses {$indicateurs} indicateur(s) ont été supprimés.");
    }

    /**
     * Lots sans empreinte, plus celui de l'empreinte en cours de modification.
     */
    private function lotsDisponibles(?EmpreinteCarbone $empreinte = null): Collection
    {
        return Lot::with('produit:id,nom')
            ->where(fn ($q) => $q->doesntHave('empreinteCarbone')
                ->when($empreinte, fn ($q) => $q->orWhere('id', $empreinte->lot_id)))
            ->latest('date_production')
            ->get();
    }
}
