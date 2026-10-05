<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\View\View;

class AccueilController extends Controller
{
    public function __invoke(): View
    {
        $produitsRecents = Produit::query()
            ->with(['categorie', 'certifications' => fn ($q) => $q->valides()->with('organisme:id,nom')])
            ->avecScore()
            ->latest()
            ->limit(4)
            ->get();

        return view('pages.front.home', ['produitsRecents' => $produitsRecents]);
    }
}
