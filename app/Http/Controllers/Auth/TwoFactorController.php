<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function challenge(): View|RedirectResponse
    {
        if (! session('login.id')) {
            return redirect()->route('login');
        }

        return view('pages.auth.two-factor-challenge');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ], [], ['code' => 'code']);

        $userId = session('login.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);

        if ($user->validerCodeDeuxFacteurs($request->string('code')->toString())) {
            Auth::login($user, (bool) session('login.remember'));
            $request->session()->forget(['login.id', 'login.remember']);
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();

            return redirect()->intended($user->espaceParDefaut());
        }

        throw ValidationException::withMessages([
            'code' => 'Code invalide ou expiré. Réessayez ou utilisez un code de récupération.',
        ]);
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $secret = null;
        $qr = null;
        $otpauth = null;

        if (! $user->hasTwoFactorEnabled() && session('two_factor.setup_secret')) {
            $secret = session('two_factor.setup_secret');
            $otpauth = Totp::uri($secret, $user->email);
            $qr = Totp::qrUrl($otpauth);
        }

        return view('pages.auth.two-factor-settings', [
            'user' => $user,
            'secret' => $secret,
            'qr' => $qr,
            'otpauth' => $otpauth,
            'recoveryCodes' => session('two_factor.recovery_codes_plain'),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $secret = Totp::genererSecret();
        $codes = Totp::codesRecuperation();

        $request->session()->put('two_factor.setup_secret', $secret);
        $request->session()->put('two_factor.recovery_codes_plain', $codes);

        $request->user()->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(array_map(
                fn (string $c) => password_hash($c, PASSWORD_BCRYPT),
                $codes,
            ))),
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('two-factor.show')
            ->with('status', 'Scannez le QR code puis confirmez avec un code à 6 chiffres.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ], [], ['code' => 'code']);

        $user = $request->user();
        $secret = $user->twoFactorSecret();

        if (! $secret || ! Totp::verifier($secret, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => 'Le code est incorrect. Vérifiez l\'heure de votre téléphone.',
            ]);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $request->session()->forget('two_factor.setup_secret');

        return redirect()->route('two-factor.show')
            ->with('status', 'Authentification à deux facteurs activée. Conservez vos codes de récupération.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.current_password' => 'Mot de passe incorrect.',
        ], ['password' => 'mot de passe']);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget(['two_factor.setup_secret', 'two_factor.recovery_codes_plain']);

        return redirect()->route('two-factor.show')->with('status', 'L\'authentification à deux facteurs a été désactivée.');
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.current_password' => 'Mot de passe incorrect.',
        ], ['password' => 'mot de passe']);

        if (! $request->user()->hasTwoFactorEnabled()) {
            return back()->withErrors(['password' => 'Activez d\'abord la 2FA.']);
        }

        $codes = Totp::codesRecuperation();
        $request->user()->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(array_map(
                fn (string $c) => password_hash($c, PASSWORD_BCRYPT),
                $codes,
            ))),
        ])->save();

        $request->session()->put('two_factor.recovery_codes_plain', $codes);

        return redirect()->route('two-factor.show')->with('status', 'Nouveaux codes de récupération générés.');
    }
}
