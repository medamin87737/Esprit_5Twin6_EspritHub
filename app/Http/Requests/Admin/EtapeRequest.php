<?php

namespace App\Http\Requests\Admin;

use App\Models\Lot;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EtapeRequest extends FormRequest
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
            'lot_id' => ['required', 'integer', 'exists:lots,id'],
            'acteur_id' => ['required', 'integer', 'exists:acteurs,id'],
            'type_etape' => ['required', Rule::in(array_keys(config('nutritrace.options.etape_types')))],
            'date_heure' => ['bail', 'required', 'date', $this->pasAvantLaProduction(...)],
            'lieu' => ['required', 'string', 'min:2', 'max:150'],
            'mode_transport' => ['required', Rule::in(array_keys(config('nutritrace.options.modes_transport')))],
            'remarques' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'lot_id' => 'lot',
            'acteur_id' => 'acteur',
            'type_etape' => 'type d\'étape',
            'date_heure' => 'date et heure',
            'lieu' => 'lieu',
            'mode_transport' => 'mode de transport',
            'remarques' => 'remarques',
        ];
    }

    private function pasAvantLaProduction(string $attribute, mixed $value, Closure $fail): void
    {
        $lot = Lot::find($this->input('lot_id'));

        if ($lot && Carbon::parse($value)->startOfDay()->lt($lot->date_production)) {
            $fail("La date de l'étape ne peut pas précéder la production du lot ({$lot->date_production->format('d/m/Y')}).");
        }
    }
}
