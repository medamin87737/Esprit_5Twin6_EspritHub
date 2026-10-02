<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActeurRequest extends FormRequest
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
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            'type_acteur_id' => ['required', 'integer', 'exists:type_acteurs,id'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('acteurs', 'email')->ignore($this->route('acteur'))],
            'telephone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{8,30}$/'],
            'date_inscription' => ['required', 'date', 'before_or_equal:today'],
            'adresse' => ['required', 'string', 'max:255'],
            'pays' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'produits' => ['nullable', 'array'],
            'produits.*' => ['integer', 'distinct', 'exists:produits,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom de l\'acteur',
            'type_acteur_id' => 'type d\'acteur',
            'email' => 'e-mail',
            'telephone' => 'téléphone',
            'date_inscription' => 'date d\'inscription',
            'adresse' => 'adresse',
            'pays' => 'pays',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'produits' => 'produits',
            'produits.*' => 'produit',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telephone.regex' => 'Le numéro de téléphone n\'est pas valide (ex. +216 71 000 000).',
        ];
    }
}
