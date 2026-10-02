<?php

namespace App\Http\Requests\Admin;

use App\Models\EmpreinteCarbone;
use App\Models\Etape;
use App\Models\Indicateur;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndicateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'empreinte_carbone_id' => ['required', 'integer', 'exists:empreinte_carbones,id'],
            'etape_id' => ['bail', 'nullable', 'integer', 'exists:etapes,id', $this->etapeDuMemeLot(...)],
            'type' => ['required', Rule::in(array_keys(config('nutritrace.options.indicateur_types')))],
            'valeur' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'unite' => ['bail', 'required', Rule::in(array_keys(config('nutritrace.options.unites'))), $this->uniteDuType(...)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'empreinte_carbone_id' => 'empreinte carbone',
            'etape_id' => 'étape',
            'type' => 'type',
            'valeur' => 'valeur',
            'unite' => 'unité',
        ];
    }

    private function etapeDuMemeLot(string $attribute, mixed $value, Closure $fail): void
    {
        $empreinte = EmpreinteCarbone::find($this->input('empreinte_carbone_id'));
        $etape = Etape::find($value);

        if ($empreinte && $etape && $etape->lot_id !== $empreinte->lot_id) {
            $fail('Cette étape appartient à un autre lot que celui de l\'empreinte choisie.');
        }
    }

    private function uniteDuType(string $attribute, mixed $value, Closure $fail): void
    {
        $attendue = Indicateur::UNITES[$this->input('type')] ?? null;

        if ($attendue && $value !== $attendue) {
            $fail("Pour ce type d'indicateur, l'unité doit être « {$attendue} ».");
        }
    }
}
