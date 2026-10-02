<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TypeActeurRequest extends FormRequest
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
            'libelle' => ['required', 'string', 'min:2', 'max:100', Rule::unique('type_acteurs', 'libelle')->ignore($this->route('type_acteur'))],
            'role_chaine' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'libelle' => 'libellé',
            'role_chaine' => 'rôle dans la chaîne',
            'description' => 'description',
        ];
    }
}
