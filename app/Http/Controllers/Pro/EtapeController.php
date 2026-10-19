<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Etape;
use App\Models\Lot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Étapes saisies par l'acteur courant du lot, limitées aux types autorisés pour son rôle.
 */
class EtapeController extends Controller
{
    public function create(Request $request, Lot $lot): View
    {
        $acteur = $request->user()->acteur;

        return view('pages.pro.etapes.form', [
            'lot' => $lot,
            'etape' => new Etape(['lieu' => $acteur->adresse, 'date_heure' => now()->startOfMinute()]),
            'types' => $this->typesAutorises($request),
        ]);
    }

    public function store(Request $request, Lot $lot): RedirectResponse
    {
        $data = $this->valider($request, $lot);

        $etape = new Etape($data);
        $etape->lot()->associate($lot);
        $etape->acteur()->associate($request->user()->acteur);
        $etape->save();

        return redirect()->route('pro.lots.show', $lot)
            ->with('status', "Étape « {$etape->typeLabel()} » enregistrée. Vous pouvez y ajouter des indicateurs environnementaux.");
    }

    public function edit(Request $request, Etape $etape): View
    {
        return view('pages.pro.etapes.form', [
            'lot' => $etape->lot,
            'etape' => $etape,
            'types' => $this->typesAutorises($request),
        ]);
    }

    public function update(Request $request, Etape $etape): RedirectResponse
    {
        $etape->update($this->valider($request, $etape->lot, $etape));

        return redirect()->route('pro.lots.show', $etape->lot)->with('status', 'L\'étape a été modifiée.');
    }

    public function destroy(Etape $etape): RedirectResponse
    {
        $lot = $etape->lot;
        $lot->supprimerEtape($etape);

        return redirect()->route('pro.lots.show', $lot)->with('status', 'L\'étape et ses indicateurs ont été supprimés.');
    }

    /**
     * @return array<string, string>
     */
    private function typesAutorises(Request $request): array
    {
        return array_intersect_key(
            config('nutritrace.options.etape_types'),
            array_flip((array) $request->user()->droitPro('etapes')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function valider(Request $request, Lot $lot, ?Etape $etape = null): array
    {
        $precedente = $lot->etapes()
            ->when($etape, fn ($q) => $q->whereKeyNot($etape->id)->where('date_heure', '<=', $etape->date_heure))
            ->max('date_heure');
        $minimum = $precedente ?? $lot->date_production->startOfDay();

        return $request->validate([
            'type_etape' => ['required', Rule::in(array_keys($this->typesAutorises($request)))],
            'date_heure' => ['required', 'date', 'after_or_equal:'.$minimum, 'before_or_equal:now'],
            'lieu' => ['required', 'string', 'max:150'],
            'mode_transport' => ['required', Rule::in(array_keys(config('nutritrace.options.modes_transport')))],
            'remarques' => ['nullable', 'string', 'max:1000'],
        ], [
            'type_etape.in' => 'Votre rôle ne permet pas d\'ajouter ce type d\'étape.',
            'date_heure.after_or_equal' => 'La date doit être postérieure à la production du lot et à l\'étape précédente.',
            'date_heure.before_or_equal' => 'La date ne peut pas être dans le futur.',
        ], [
            'type_etape' => 'type d\'étape',
            'date_heure' => 'date et heure',
            'lieu' => 'lieu',
            'mode_transport' => 'mode de transport',
            'remarques' => 'remarques',
        ]);
    }
}
