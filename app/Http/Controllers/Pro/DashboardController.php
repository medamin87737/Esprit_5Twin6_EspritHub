<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use App\Models\Certification;
use App\Models\Lot;
use App\Models\Signalement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $acteur = $user->acteur;
        $gereProduits = Gate::allows('pro-produits');

        $lotsChezMoi = $acteur
            ? Lot::where('acteur_courant_id', $acteur->id)->with('produit:id,nom')->withCount('etapes')->latest()->limit(5)->get()
            : collect();

        $donnees = [
            'acteur' => $acteur,
            'gereProduits' => $gereProduits,
            'lotsChezMoi' => $lotsChezMoi,
            'nbLotsChezMoi' => $acteur ? Lot::where('acteur_courant_id', $acteur->id)->count() : 0,
            'nbLotsTraites' => $acteur ? Lot::concernant($acteur)->count() : 0,
            'nbAnalysesEnAttente' => $acteur ? $this->analysesDeMesLots($acteur->id, 'en_attente') : 0,
            'nbAnalysesNonConformes' => $acteur ? $this->analysesDeMesLots($acteur->id, 'non_conforme') : 0,
        ];

        if ($gereProduits) {
            $mesProduits = $user->produits()->select('id');

            $donnees += [
                'nbProduits' => $user->produits()->count(),
                'nbCertificationsValides' => Certification::valides()->whereIn('produit_id', $mesProduits)->count(),
                'nbCertificationsEnAttente' => Certification::where('statut', 'en_attente')->whereIn('produit_id', $mesProduits)->count(),
                'expirations' => Certification::valides()
                    ->whereIn('produit_id', $mesProduits)
                    ->whereDate('date_expiration', '<=', today()->addDays(30))
                    ->with('produit:id,nom')
                    ->orderBy('date_expiration')
                    ->get(),
                'signalements' => Signalement::whereIn('produit_id', $mesProduits)->with('produit:id,nom')->latest()->limit(5)->get(),
                'nbSignalementsEnAttente' => Signalement::where('statut', 'en_attente')->whereIn('produit_id', $mesProduits)->count(),
            ];
        }

        return view('pages.pro.dashboard', $donnees);
    }

    private function analysesDeMesLots(int $acteurId, string $resultat): int
    {
        return Analyse::resultat($resultat)->whereHas('lot', fn ($q) => $q->where('acteur_courant_id', $acteurId))->count();
    }
}
