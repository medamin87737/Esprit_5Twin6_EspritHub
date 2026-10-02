<?php

namespace App\Http\Requests\Admin;

use App\Models\Certification;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class CertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
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
        return [
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
            'organisme_id' => ['required', 'integer', 'exists:organismes,id'],
            'type' => ['bail', 'required', Rule::in(array_keys(config('nutritrace.options.certification_types'))), $this->pasDeDoublonValide(...)],
            'numero' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9-]{3,39}$/', Rule::unique('certifications', 'numero')->ignore($this->route('certification'))],
            'statut' => ['bail', 'required', Rule::in(array_keys(config('nutritrace.options.certification_statuts'))), $this->coherentAvecLaDate(...)],
            'date_obtention' => ['required', 'date', 'before_or_equal:today'],
            'date_expiration' => ['required', 'date', 'after:date_obtention'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'produit_id' => 'produit',
            'organisme_id' => 'organisme',
            'type' => 'type de label',
            'numero' => 'numéro',
            'statut' => 'statut',
            'date_obtention' => 'date d\'obtention',
            'date_expiration' => 'date d\'expiration',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero.regex' => 'Le numéro ne peut contenir que des lettres, des chiffres et des tirets (ex. BIO-TN-2026-001).',
            'numero.unique' => 'Ce numéro de certification est déjà attribué.',
        ];
    }

    /**
     * Un produit ne peut avoir qu'une seule certification valide par type de label.
     */
    private function pasDeDoublonValide(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->input('statut') !== 'valide' || ! $this->filled('produit_id')) {
            return;
        }

        $existe = Certification::valides()
            ->where('produit_id', $this->input('produit_id'))
            ->where('type', $value)
            ->when($this->route('certification'), fn ($q, $certification) => $q->whereKeyNot($certification->id))
            ->exists();

        if ($existe) {
            $fail('Ce produit possède déjà une certification de ce type en cours de validité.');
        }
    }

    /**
     * Une certification dont la date d'expiration est passée ne peut pas être enregistrée comme valide.
     */
    private function coherentAvecLaDate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== 'valide' || ! $this->filled('date_expiration')) {
            return;
        }

        try {
            $expiration = Carbon::parse((string) $this->input('date_expiration'))->startOfDay();
        } catch (Throwable) {
            return;
        }

        if ($expiration->lt(today())) {
            $fail('Une certification dont la date d\'expiration est dépassée ne peut pas être « Valide ».');
        }
    }
}
