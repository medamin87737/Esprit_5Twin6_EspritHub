<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LaboratoireRequest extends FormRequest
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
            'nom' => ['required', 'string', 'min:2', 'max:120', Rule::unique('laboratoires', 'nom')->ignore($this->route('laboratoire'))],
            'ville' => ['required', 'string', 'min:2', 'max:80'],
            'pays' => ['required', 'string', 'min:2', 'max:80'],
            'accreditation' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('laboratoires', 'email')->ignore($this->route('laboratoire'))],
            'telephone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{8,30}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom du laboratoire',
            'ville' => 'ville',
            'pays' => 'pays',
            'accreditation' => 'accréditation',
            'email' => 'e-mail',
            'telephone' => 'téléphone',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.unique' => 'Un laboratoire portant ce nom existe déjà.',
            'email.unique' => 'Cet e-mail est déjà utilisé par un autre laboratoire.',
            'telephone.regex' => 'Le téléphone ne doit contenir que des chiffres, espaces, points, tirets ou parenthèses (8 caractères min.).',
        ];
    }
}
