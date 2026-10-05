<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Etape;
use App\Models\Indicateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IndicateurController extends Controller
{
    public function create(Etape $etape): View
    {
        $etape->load(['lot.produit', 'lot.empreinteCarbone', 'indicateurs']);

        return view('pages.pro.indicateurs.create', ['etape' => $etape]);
    }

    public function store(Request $request, Etape $etape): RedirectResponse
    {
        $types = array_keys(config('nutritrace.options.indicateur_types'));

        $data = $request->validate([
            'type' => ['required', Rule::in($types)],
            'valeur' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'unite' => ['required', Rule::in([Indicateur::UNITES[$request->input('type')] ?? null])],
        ], [
            'unite.in' => 'L\'unité ne correspond pas au type choisi (eau en L, énergie en kWh, transport en km, emballage en kg).',
        ], [
            'type' => 'type d\'indicateur',
            'valeur' => 'valeur',
            'unite' => 'unité',
        ]);

        $empreinte = $etape->lot->enregistrerIndicateur($etape, $data['type'], (float) $data['valeur']);

        return redirect()->route('pro.lots.show', $etape->lot)->with('status', sprintf(
            'Indicateur enregistré. Score recalculé : %s (%s kg CO₂e par unité).',
            $empreinte->score,
            number_format($empreinte->co2_total, 2, ',', ' '),
        ));
    }
}
