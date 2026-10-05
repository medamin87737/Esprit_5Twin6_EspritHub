<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelController extends Controller
{
    public function __invoke(Request $request): View
    {
        $numero = strtoupper(trim((string) $request->query('numero')));

        $certification = $numero === '' ? null : Certification::query()
            ->with(['produit.categorie', 'organisme'])
            ->where('statut', '!=', 'en_attente')
            ->where('numero', $numero)
            ->first();

        // Une déclaration en attente n'a pas encore été vérifiée : elle n'est pas publiée.
        $certifications = Certification::query()
            ->with(['produit:id,nom', 'organisme:id,nom'])
            ->where('statut', '!=', 'en_attente')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderBy('date_expiration', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('pages.front.certifications.index', [
            'certification' => $certification,
            'certifications' => $certifications,
            'exemples' => Certification::valides()->latest('date_obtention')->limit(3)->pluck('numero'),
        ]);
    }
}
