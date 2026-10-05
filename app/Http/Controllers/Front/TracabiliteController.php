<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TracabiliteController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $numero = strtoupper(trim((string) $request->query('numero')));

        if ($numero !== '' && ($lot = Lot::firstWhere('numero_lot', $numero))) {
            return redirect()->route('front.lots.show', $lot);
        }

        return view('pages.front.lots.search', [
            'numero' => $numero,
            'exemples' => Lot::has('etapes')->latest('date_production')->limit(3)->pluck('numero_lot'),
        ]);
    }
}
