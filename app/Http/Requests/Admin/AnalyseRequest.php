<?php

namespace App\Http\Requests\Admin;

use App\Models\Analyse;
use App\Models\Lot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Utilisée par le Back Office (lot choisi dans le formulaire) et par l'espace pro
 * (lot imposé par l'URL, droits vérifiés par AnalysePolicy / LotPolicy sur la route).
 */
class AnalyseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->estPro() ? (bool) $this->user()?->isPro() : (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('numero')) {
            $this->merge(['numero' => strtoupper(trim((string) $this->input('numero')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $lot = $this->lotConcerne();

        $rules = [
            'laboratoire_id' => ['required', 'integer', 'exists:laboratoires,id'],
            'type' => ['required', Rule::in(array_keys(config('nutritrace.options.analyse_types')))],
            'numero' => ['required', 'string', 'max:20', 'regex:/^ANA-\d{4}-\d{4}$/', Rule::unique('analyses', 'numero')->ignore($this->route('analyse'))],
            'date_prelevement' => array_filter(['required', 'date', 'before_or_equal:today',
                $lot?->date_production ? 'after_or_equal:'.$lot->date_production->toDateString() : null]),
            'resultat' => ['required', Rule::in(array_keys(config('nutritrace.options.analyse_resultats')))],
            'date_resultat' => ['nullable', 'prohibited_if:resultat,en_attente', 'required_unless:resultat,en_attente', 'date', 'after_or_equal:date_prelevement', 'before_or_equal:today'],
            'commentaire' => ['nullable', 'required_if:resultat,non_conforme', 'string', 'max:1000'],
            'rapport' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];

        if (! $this->estPro()) {
            $rules = ['lot_id' => ['required', 'integer', 'exists:lots,id']] + $rules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'lot_id' => 'lot',
            'laboratoire_id' => 'laboratoire',
            'type' => 'type d\'analyse',
            'numero' => 'numéro',
            'date_prelevement' => 'date de prélèvement',
            'resultat' => 'résultat',
            'date_resultat' => 'date du résultat',
            'commentaire' => 'commentaire',
            'rapport' => 'rapport PDF',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero.regex' => 'Le numéro doit respecter le format ANA-AAAA-NNNN (ex. ANA-2026-0001).',
            'numero.unique' => 'Ce numéro d\'analyse est déjà utilisé.',
            'date_prelevement.after_or_equal' => 'Le prélèvement ne peut pas précéder la production du lot.',
            'date_resultat.required_unless' => 'Indiquez la date à laquelle le laboratoire a rendu son résultat.',
            'date_resultat.prohibited_if' => 'Une analyse en attente n\'a pas encore de date de résultat.',
            'commentaire.required_if' => 'Décrivez la non-conformité constatée par le laboratoire.',
            'rapport.mimes' => 'Le rapport doit être un fichier PDF.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function donnees(): array
    {
        return collect($this->validated())->except('rapport')->all();
    }

    private function estPro(): bool
    {
        return $this->routeIs('pro.*');
    }

    private function lotConcerne(): ?Lot
    {
        $lot = $this->route('lot');
        $analyse = $this->route('analyse');

        return match (true) {
            $this->estPro() && $lot instanceof Lot => $lot,
            $this->estPro() && $analyse instanceof Analyse => $analyse->lot,
            default => Lot::find($this->integer('lot_id')),
        };
    }
}
