<?php

namespace App\Http\Requests\Admin;

use App\Models\Produit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $produit = $this->route('produit');

        return $produit instanceof Produit
            ? (bool) $this->user()?->can('update', $produit)
            : (bool) $this->user()?->can('create', Produit::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            'code_barres' => ['required', 'digits:13', Rule::unique('produits', 'code_barres')->ignore($this->route('produit'))],
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'origine' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'composition' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($this->routeIs('admin.*')) {
            $rules['fournisseur_id'] = ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'fournisseur')];
        }

        return $rules;
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
            'fournisseur_id' => 'fournisseur',
            'origine' => 'origine',
            'description' => 'description',
            'composition' => 'composition',
            'image' => 'image',
        ];
    }
}
