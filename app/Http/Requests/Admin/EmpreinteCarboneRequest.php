<?php

namespace App\Http\Requests\Admin;

use App\Models\Lot;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EmpreinteCarboneRequest extends FormRequest
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
            'lot_id' => ['required', 'integer', 'exists:lots,id', Rule::unique('empreinte_carbones', 'lot_id')->ignore($this->route('empreinte'))],
            'co2_total' => ['required', 'numeric', 'min:0', 'max:999999'],
            'methode' => ['required', 'string', 'min:2', 'max:60'],
            'date_calcul' => ['bail', 'required', 'date', 'before_or_equal:today', $this->pasAvantLaProduction(...)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'lot_id' => 'lot',
            'co2_total' => 'CO₂ total',
            'methode' => 'méthode de calcul',
            'date_calcul' => 'date du calcul',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lot_id.unique' => 'Ce lot a déjà une empreinte carbone : modifiez-la plutôt que d\'en créer une nouvelle.',
        ];
    }

    private function pasAvantLaProduction(string $attribute, mixed $value, Closure $fail): void
    {
        $lot = Lot::find($this->input('lot_id'));

        if ($lot && Carbon::parse($value)->lt($lot->date_production)) {
            $fail("Le calcul ne peut pas précéder la production du lot ({$lot->date_production->format('d/m/Y')}).");
        }
    }
}
