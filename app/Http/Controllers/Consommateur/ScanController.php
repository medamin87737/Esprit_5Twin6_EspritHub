<?php

namespace App\Http\Controllers\Consommateur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $scans = $request->user()->scans()
            ->with(['lot:id,produit_id,numero_lot', 'lot.produit:id,nom', 'lot.empreinteCarbone:id,lot_id,score,co2_total'])
            ->latest()
            ->paginate(15);

        return view('pages.front.consommateur.scans', ['scans' => $scans]);
    }
}
