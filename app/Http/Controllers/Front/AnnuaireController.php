<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Acteur;
use App\Models\TypeActeur;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnuaireController extends Controller
{
    public function __invoke(Request $request): View
    {
        $recherche = Acteur::query()
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type_acteur_id', $request->input('type')))
            ->when($request->filled('pays'), fn ($q) => $q->where('pays', $request->input('pays')));

        $acteurs = (clone $recherche)
            ->with(['typeActeur', 'produits' => fn ($q) => $q->orderBy('nom')])
            ->orderBy('nom')
            ->paginate(12)
            ->withQueryString();

        $points = (clone $recherche)
            ->with('typeActeur:id,libelle')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'type_acteur_id', 'nom', 'adresse', 'pays', 'latitude', 'longitude'])
            ->map(fn (Acteur $acteur) => [
                'lat' => $acteur->latitude,
                'lng' => $acteur->longitude,
                'titre' => $acteur->nom,
                'texte' => $acteur->typeActeur?->libelle.' · '.$acteur->adresse.', '.$acteur->pays,
            ])
            ->all();

        return view('pages.front.acteurs.index', [
            'acteurs' => $acteurs,
            'points' => $points,
            'typeActeurs' => TypeActeur::orderBy('libelle')->get(),
            'pays' => Acteur::query()->distinct()->orderBy('pays')->pluck('pays'),
        ]);
    }
}
