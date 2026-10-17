<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\EmpreinteCarbone;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EcoScoreController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = EmpreinteCarbone::query()
            ->with(['lot.produit', 'indicateurs'])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('lot.produit', fn ($p) => $p
                ->where('nom', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('score'), fn ($q) => $q->where('score', $request->input('score')))
            ->orderBy('co2_total');

        $co2Max = (float) ((clone $query)->max('co2_total') ?? 0);

        $empreintes = $query->paginate(15)->withQueryString();

        return view('pages.front.empreintes.index', [
            'empreintes' => $empreintes,
            'co2Max' => $co2Max,
        ]);
    }
}
