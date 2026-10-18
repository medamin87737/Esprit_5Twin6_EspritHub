<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

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

        $emailEnvoye = true;
        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            $emailEnvoye = false;
            Log::warning('Inscription : e-mail de vérification non envoyé', [
                'user_id' => $user->id,
                'email' => $user->email,
                'erreur' => $e->getMessage(),
            ]);
        }

        $redirect = redirect()->route('verification.notice');

        if ($emailEnvoye) {
            return $redirect->with('status', "Bienvenue {$user->name} ! Vérifiez votre e-mail pour activer votre compte.");
        }

        // En local : lien de vérification affiché si Resend refuse (mode test).
        if (app()->environment('local')) {
            $url = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
                ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
            );

            return $redirect
                ->with('status', "Bienvenue {$user->name} ! Resend n'a pas pu envoyer l'e-mail (mode test). Utilisez le lien ci-dessous pour activer le compte.")
                ->with('verification_url', $url);
        }

        return $redirect->with('status', "Bienvenue {$user->name} ! Compte créé, mais l'e-mail n'a pas pu être envoyé. Réessayez via « Renvoyer ».");
    }
}
