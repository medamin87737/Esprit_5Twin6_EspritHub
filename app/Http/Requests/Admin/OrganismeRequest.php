<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganismeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('site_web') && ! preg_match('#^https?://#i', (string) $this->input('site_web'))) {
            $this->merge(['site_web' => 'https://'.trim((string) $this->input('site_web'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:2', 'max:120', Rule::unique('organismes', 'nom')->ignore($this->route('organisme'))],
            'pays' => ['required', 'string', 'min:2', 'max:80'],
            'site_web' => ['required', 'url:http,https', 'max:255'],
            'accreditation' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom de l\'organisme',
            'pays' => 'pays',
            'site_web' => 'site web',
            'accreditation' => 'accréditation',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.unique' => 'Un organisme portant ce nom existe déjà.',
        ];
    }
}
