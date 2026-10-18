<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Throwable;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->espaceParDefaut());
        }

        return view('pages.auth.verify-email', [
            'expireMinutes' => (int) Config::get('auth.verification.expire', 60),
        ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->espaceParDefaut().'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($request->user()->espaceParDefaut())
            ->with('status', 'Votre adresse e-mail est vérifiée. Merci !');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->espaceParDefaut());
        }

        try {
            $request->user()->sendEmailVerificationNotification();

            return back()->with('status', 'Un nouvel e-mail de vérification vient d\'être envoyé.');
        } catch (Throwable $e) {
            return back()->with('error', 'Envoi impossible : '.$e->getMessage());
        }
    }
}
