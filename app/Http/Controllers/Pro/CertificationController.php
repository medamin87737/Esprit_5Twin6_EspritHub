<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Models\Organisme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Certifications des produits du professionnel : consultation et déclaration
 * (statut initial EN_ATTENTE, validé ensuite par l'administration).
 */
class CertificationController extends Controller
{
    public function index(Request $request): View
    {
        $mesProduits = $request->user()->produits()->select('id');

        $certifications = Certification::query()
            ->with(['produit:id,nom', 'organisme:id,nom'])
            ->whereIn('produit_id', $mesProduits)
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->input('statut')))
            ->orderBy('date_expiration')
            ->paginate(15)
            ->withQueryString();

        $expirations = Certification::valides()
            ->whereIn('produit_id', $mesProduits)
            ->whereDate('date_expiration', '<=', today()->addDays(30))
            ->count();

        return view('pages.pro.certifications.index', [
            'certifications' => $certifications,
            'expirations' => $expirations,
        ]);
    }

    public function create(Request $request): View
    {
        return view('pages.pro.certifications.create', [
            'produits' => $request->user()->produits()->orderBy('nom')->pluck('nom', 'id'),
            'organismes' => Organisme::orderBy('nom')->pluck('nom', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['numero' => strtoupper(trim((string) $request->input('numero')))]);

        $data = $request->validate([
            'produit_id' => ['required', 'integer', Rule::exists('produits', 'id')->where('proprietaire_id', $request->user()->id)],
            'organisme_id' => ['required', 'integer', 'exists:organismes,id'],
            'type' => ['required', Rule::in(array_keys(config('nutritrace.options.certification_types')))],
            'numero' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9-]{3,39}$/', 'unique:certifications,numero'],
            'date_obtention' => ['required', 'date', 'before_or_equal:today'],
            'date_expiration' => ['required', 'date', 'after:today', 'after:date_obtention'],
        ], [
            'produit_id.exists' => 'Choisissez un de vos produits.',
            'numero.regex' => 'Le numéro ne peut contenir que des lettres, des chiffres et des tirets (ex. BIO-TN-2026-001).',
            'numero.unique' => 'Ce numéro de certification est déjà enregistré.',
            'date_expiration.after' => 'La date d\'expiration doit être future et postérieure à la date d\'obtention.',
        ], [
            'produit_id' => 'produit',
            'organisme_id' => 'organisme',
            'type' => 'type de label',
            'numero' => 'numéro',
            'date_obtention' => 'date d\'obtention',
            'date_expiration' => 'date d\'expiration',
        ]);

        $certification = Certification::create($data + ['statut' => 'en_attente']);

        return redirect()->route('pro.certifications.index')
            ->with('status', "Certification {$certification->numero} déclarée : elle est en attente de vérification par NutriTrace.");
    }
}
