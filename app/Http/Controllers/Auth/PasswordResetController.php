<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('pages.auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [], ['email' => 'adresse e-mail']);

        $email = $request->string('email')->toString();
        $user = User::where('email', $email)->first();

        // Même message si l'e-mail n'existe pas (sécurité).
        if (! $user) {
            return back()->with('status', 'Si un compte existe pour cette adresse, un lien de réinitialisation vient d\'être envoyé (valable '.config('auth.passwords.users.expire').' minutes).');
        }

        $token = Password::broker()->createToken($user);
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        try {
            $user->sendPasswordResetNotification($token);

            return back()->with('status', 'Un lien de réinitialisation vous a été envoyé par e-mail (valable '.config('auth.passwords.users.expire').' minutes).');
        } catch (\Throwable $e) {
            report($e);

            // En local / démo : on montre le lien pour que la fonctionnalité reste testable.
            if (config('app.debug')) {
                return back()
                    ->with('status', 'L\'e-mail n\'a pas pu partir ('.$e->getMessage().'). Utilisez le lien ci-dessous pour tester.')
                    ->with('reset_url', $url);
            }

            throw ValidationException::withMessages([
                'email' => 'Impossible d\'envoyer l\'e-mail pour le moment. Vérifiez RESEND_API_KEY et le domaine expéditeur sur resend.com.',
            ]);
        }
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [], [
            'email' => 'adresse e-mail',
            'password' => 'mot de passe',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->string('password')->toString()),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return redirect()->route('login')->with('status', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');
    }
}
