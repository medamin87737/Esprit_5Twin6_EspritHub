<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('numero_lot')) {
            $this->merge(['numero_lot' => strtoupper(trim((string) $this->input('numero_lot')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero_lot' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9-]{3,29}$/', Rule::unique('lots', 'numero_lot')->ignore($this->route('lot'))],
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
            'quantite' => ['required', 'integer', 'min:1', 'max:10000000'],
            'date_production' => ['required', 'date', 'before_or_equal:today'],
            'date_peremption' => ['required', 'date', 'after:date_production'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'numero_lot' => 'numéro de lot',
            'produit_id' => 'produit',
            'quantite' => 'quantité',
            'date_production' => 'date de production',
            'date_peremption' => 'date de péremption',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_lot.regex' => 'Le numéro de lot ne peut contenir que des lettres, des chiffres et des tirets (ex. LOT-2026-0001).',
        ];
    }
}
