<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LotRequest;
use App\Models\Lot;
use App\Models\Produit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LotController extends Controller
{
    public function index(Request $request): View
    {
        $lots = Lot::query()
            ->with(['produit', 'empreinteCarbone:id,lot_id,score'])
            ->withCount('etapes')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('numero_lot', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('produit', fn ($p) => $p->where('nom', 'like', '%'.$request->input('q').'%'))))
            ->when($request->filled('produit'), fn ($q) => $q->where('produit_id', $request->input('produit')))
            ->latest('date_production')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.lots.index', [
            'lots' => $lots,
            'produits' => Produit::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.lots.create', ['produits' => Produit::orderBy('nom')->get(['id', 'nom'])]);
    }

    public function store(LotRequest $request): RedirectResponse
    {
        $lot = Lot::create($request->validated());

        return redirect()->route('admin.lots.show', $lot)
            ->with('success', "Le lot « {$lot->numero_lot} » a été créé. Ajoutez maintenant ses étapes.");
    }

    public function show(Lot $lot): View
    {
        $lot->load([
            'produit.categorie',
            'etapes' => fn ($q) => $q->with('acteur.typeActeur')->withCount('indicateurs')->orderBy('date_heure'),
            'empreinteCarbone' => fn ($q) => $q->withCount('indicateurs'),
        ]);

        return view('pages.admin.lots.show', ['lot' => $lot]);
    }

    public function edit(Lot $lot): View
    {
        return view('pages.admin.lots.edit', [
            'lot' => $lot,
            'produits' => Produit::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function update(LotRequest $request, Lot $lot): RedirectResponse
    {
        $lot->update($request->validated());

        return redirect()->route('admin.lots.index')
            ->with('success', "Le lot « {$lot->numero_lot} » a été mis à jour.");
    }

    public function destroy(Lot $lot): RedirectResponse
    {
        $etapes = $lot->etapes()->count();
        $lot->delete();

        return redirect()->route('admin.lots.index')
            ->with('success', "Le lot « {$lot->numero_lot} » et ses {$etapes} étape(s) ont été supprimés.");
    }
}
