<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Signalement;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Signalements des consommateurs sur les produits du professionnel (lecture seule).
 */
class SignalementController extends Controller
{
    public function __invoke(Request $request): View
    {
        $signalements = Signalement::query()
            ->with('produit:id,nom')
            ->whereIn('produit_id', $request->user()->produits()->select('id'))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->input('statut')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.pro.signalements.index', ['signalements' => $signalements]);
    }
}
