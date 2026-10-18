<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'Indiquez une adresse e-mail valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ], [
            'email' => 'adresse e-mail',
            'password' => 'mot de passe',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Ces identifiants ne correspondent à aucun compte.',
            ]);
        }

        $user = $request->user();

        if (! $user->active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Ce compte est suspendu. Contactez un administrateur NutriTrace.',
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        // MustVerifyEmail : bloquer l'accès tant que l'e-mail n'est pas confirmé.
        if (! $user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
                $emailEnvoye = true;
            } catch (Throwable $e) {
                $emailEnvoye = false;
                Log::warning('Connexion : e-mail de vérification non envoyé', [
                    'user_id' => $user->id,
                    'erreur' => $e->getMessage(),
                ]);
            }

            $redirect = redirect()->route('verification.notice');

            if ($emailEnvoye) {
                return $redirect->with('status', 'Un e-mail de confirmation a été envoyé. Ouvrez-le pour activer votre compte.');
            }

            return $redirect->with('error', 'Impossible d\'envoyer l\'e-mail. Réessayez avec « Renvoyer l\'e-mail ».');
        }

        return redirect()->intended($user->espaceParDefaut());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
