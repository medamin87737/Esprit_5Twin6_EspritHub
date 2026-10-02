<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProduitRequest extends FormRequest
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
            'code_barres' => ['required', 'digits:13', Rule::unique('produits', 'code_barres')->ignore($this->route('produit'))],
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'origine' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'composition' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom du produit',
            'code_barres' => 'code-barres',
            'categorie_id' => 'catégorie',
            'origine' => 'origine',
            'description' => 'description',
            'composition' => 'composition',
            'image' => 'image',
        ];
    }
}
