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
        $acteurs = Acteur::query()
            ->with(['typeActeur', 'produits' => fn ($q) => $q->orderBy('nom')])
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type_acteur_id', $request->input('type')))
            ->when($request->filled('pays'), fn ($q) => $q->where('pays', $request->input('pays')))
            ->orderBy('nom')
            ->paginate(12)
            ->withQueryString();

        return view('pages.front.acteurs.index', [
            'acteurs' => $acteurs,
            'typeActeurs' => TypeActeur::orderBy('libelle')->get(),
            'pays' => Acteur::query()->distinct()->orderBy('pays')->pluck('pays'),
        ]);
    }
}
