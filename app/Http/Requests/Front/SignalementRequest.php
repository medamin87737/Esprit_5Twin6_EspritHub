<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Les droits (consommateur, propriétaire, statut en attente) sont vérifiés par les routes.
 */
class SignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motif' => ['required', Rule::in(array_keys(config('nutritrace.options.signalement_motifs')))],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'preuve' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motif' => 'motif',
            'description' => 'description',
            'preuve' => 'preuve',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preuve.mimes' => 'La preuve doit être une image (JPG, PNG, WebP) ou un PDF.',
            'preuve.max' => 'La preuve ne doit pas dépasser 4 Mo.',
        ];
    }
}
