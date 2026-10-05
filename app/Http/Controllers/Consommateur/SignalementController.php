<?php

namespace App\Http\Controllers\Consommateur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\SignalementRequest;
use App\Models\Produit;
use App\Models\Signalement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SignalementController extends Controller
{
    public function index(Request $request): View
    {
        $signalements = $request->user()->signalements()
            ->with('produit:id,nom')
            ->latest()
            ->paginate(10);

        return view('pages.front.consommateur.signalements.index', ['signalements' => $signalements]);
    }

    public function create(Produit $produit): View
    {
        return view('pages.front.consommateur.signalements.form', [
            'produit' => $produit,
            'signalement' => new Signalement,
        ]);
    }

    public function store(SignalementRequest $request, Produit $produit): RedirectResponse
    {
        $signalement = new Signalement($request->safe()->only(['motif', 'description']));
        $signalement->produit()->associate($produit);
        $signalement->user()->associate($request->user());

        if ($request->hasFile('preuve')) {
            $signalement->preuve = $request->file('preuve')->store('signalements', 'public');
        }

        $signalement->save();

        return redirect()->route('consommateur.signalements.index')
            ->with('status', "Votre signalement sur « {$produit->nom} » a été envoyé. Il sera examiné par l'équipe NutriTrace.");
    }

    public function edit(Signalement $signalement): View
    {
        return view('pages.front.consommateur.signalements.form', [
            'produit' => $signalement->produit,
            'signalement' => $signalement,
        ]);
    }

    public function update(SignalementRequest $request, Signalement $signalement): RedirectResponse
    {
        $signalement->fill($request->safe()->only(['motif', 'description']));

        if ($request->hasFile('preuve')) {
            if ($signalement->preuve) {
                Storage::disk('public')->delete($signalement->preuve);
            }
            $signalement->preuve = $request->file('preuve')->store('signalements', 'public');
        }

        $signalement->save();

        return redirect()->route('consommateur.signalements.index')->with('status', 'Votre signalement a été modifié.');
    }

    public function destroy(Signalement $signalement): RedirectResponse
    {
        if ($signalement->preuve) {
            Storage::disk('public')->delete($signalement->preuve);
        }

        $signalement->delete();

        return redirect()->route('consommateur.signalements.index')->with('status', 'Votre signalement a été supprimé.');
    }
}
