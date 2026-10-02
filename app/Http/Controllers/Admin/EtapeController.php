<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EtapeRequest;
use App\Models\Acteur;
use App\Models\Etape;
use App\Models\Lot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EtapeController extends Controller
{
    public function index(Request $request): View
    {
        $etapes = Etape::query()
            ->with(['lot.produit', 'acteur'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('lieu', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('lot', fn ($l) => $l->where('numero_lot', 'like', '%'.$request->input('q').'%'))
                ->orWhereHas('acteur', fn ($a) => $a->where('nom', 'like', '%'.$request->input('q').'%'))))
            ->when($request->filled('type_etape'), fn ($q) => $q->where('type_etape', $request->input('type_etape')))
            ->latest('date_heure')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.etapes.index', ['etapes' => $etapes]);
    }

    public function create(): View
    {
        return view('pages.admin.etapes.create', $this->formData());
    }

    public function store(EtapeRequest $request): RedirectResponse
    {
        $etape = Etape::create($request->validated());

        return redirect()->route('admin.lots.show', $etape->lot_id)
            ->with('success', "L'étape « {$etape->typeLabel()} » a été ajoutée au lot.");
    }

    public function show(Etape $etape): View
    {
        $etape->load(['lot.produit', 'acteur.typeActeur']);

        return view('pages.admin.etapes.show', ['etape' => $etape]);
    }

    public function edit(Etape $etape): View
    {
        return view('pages.admin.etapes.edit', ['etape' => $etape] + $this->formData());
    }

    public function update(EtapeRequest $request, Etape $etape): RedirectResponse
    {
        $etape->update($request->validated());

        return redirect()->route('admin.lots.show', $etape->lot_id)
            ->with('success', "L'étape « {$etape->typeLabel()} » a été mise à jour.");
    }

    public function destroy(Etape $etape): RedirectResponse
    {
        $etape->delete();

        return back()->with('success', "L'étape « {$etape->typeLabel()} » a été supprimée.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'lots' => Lot::with('produit:id,nom')->latest('date_production')->get(),
            'acteurs' => Acteur::with('typeActeur:id,libelle')->orderBy('nom')->get(),
        ];
    }
}
