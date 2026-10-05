<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CertificationRequest;
use App\Models\Certification;
use App\Models\Organisme;
use App\Models\Produit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificationController extends Controller
{
    public function index(Request $request): View
    {
        $certifications = Certification::query()
            ->with(['produit:id,nom', 'organisme:id,nom'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('numero', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('produit', fn ($p) => $p->where('nom', 'like', '%'.$request->input('q').'%'))))
            ->when($request->filled('statut'), fn ($q) => match ($request->input('statut')) {
                'valide' => $q->valides(),
                'expiree' => $q->where(fn ($sub) => $sub->where('statut', 'expiree')
                    ->orWhere(fn ($v) => $v->where('statut', 'valide')->whereDate('date_expiration', '<', today()))),
                default => $q->where('statut', $request->input('statut')),
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('organisme'), fn ($q) => $q->where('organisme_id', $request->input('organisme')))
            ->orderBy('date_expiration')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.certifications.index', [
            'certifications' => $certifications,
            'organismes' => Organisme::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.certifications.create', $this->formData());
    }

    public function store(CertificationRequest $request): RedirectResponse
    {
        $certification = Certification::create($request->validated());

        return redirect()->route('admin.certifications.show', $certification)
            ->with('success', "La certification {$certification->numero} a été attribuée à « {$certification->produit->nom} ».");
    }

    public function show(Certification $certification): View
    {
        $certification->load(['organisme', 'produit.categorie', 'produit.proprietaire:id,name']);

        return view('pages.admin.certifications.show', [
            'certification' => $certification,
            'autresLabels' => $certification->produit->certifications()
                ->with('organisme:id,nom')
                ->whereKeyNot($certification->id)
                ->orderBy('type')
                ->get(),
        ]);
    }

    public function edit(Certification $certification): View
    {
        return view('pages.admin.certifications.edit', ['certification' => $certification] + $this->formData());
    }

    public function update(CertificationRequest $request, Certification $certification): RedirectResponse
    {
        $certification->update($request->validated());

        return redirect()->route('admin.certifications.index')
            ->with('success', "La certification {$certification->numero} a été mise à jour.");
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $certification->delete();

        return redirect()->route('admin.certifications.index')
            ->with('success', "La certification {$certification->numero} a été supprimée.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'organismes' => Organisme::orderBy('nom')->get(['id', 'nom', 'pays']),
            'produits' => Produit::orderBy('nom')->get(['id', 'nom']),
        ];
    }
}
