<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'active' => ['boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var User $cible */
                $cible = $this->route('user');
                $perdAdmin = $this->input('role') !== 'admin' || ! $this->boolean('active');

                if ($cible->is($this->user()) && $perdAdmin) {
                    $validator->errors()->add('role', 'Vous ne pouvez pas retirer vos propres droits d\'administrateur ni suspendre votre compte.');
                } elseif ($perdAdmin && $cible->isLastActiveAdmin()) {
                    $validator->errors()->add('role', 'La plateforme doit conserver au moins un administrateur actif.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom complet',
            'email' => 'adresse e-mail',
            'role' => 'rôle',
            'active' => 'statut',
            'password' => 'mot de passe',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['active' => $this->boolean('active')]);
    }
}
