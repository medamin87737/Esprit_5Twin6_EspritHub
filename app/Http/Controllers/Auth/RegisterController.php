<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public const PROFILS = [
        'consommateur' => ['libelle' => 'Consommateur', 'icone' => 'bi-person-heart'],
        'producteur' => ['libelle' => 'Producteur', 'icone' => 'bi-flower3'],
        'transformateur' => ['libelle' => 'Transformateur', 'icone' => 'bi-gear-wide-connected'],
        'distributeur' => ['libelle' => 'Distributeur', 'icone' => 'bi-truck'],
    ];

    public function create(): View
    {
        return view('pages.auth.register', ['profils' => self::PROFILS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(self::PROFILS))],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Vous devez accepter les conditions d\'utilisation.',
        ], [
            'name' => 'nom complet',
            'email' => 'adresse e-mail',
            'role' => 'profil',
            'password' => 'mot de passe',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->isPro()) {
            return redirect()->route('pro.dashboard')
                ->with('status', "Bienvenue sur NutriTrace, {$user->name} ! Complétez votre profil société pour commencer.");
        }

        return redirect()->route('home')->with('status', "Bienvenue sur NutriTrace, {$user->name} ! Votre compte a été créé.");
    }
}
