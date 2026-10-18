<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tant que l'e-mail n'est pas confirmé, l'utilisateur connecté
 * ne peut accéder qu'aux pages de vérification et à la déconnexion.
 */
class EnsureEmailIsVerifiedWhenAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->routeIs(
            'verification.notice',
            'verification.verify',
            'verification.send',
            'logout',
        )) {
            return $next($request);
        }

        return redirect()->route('verification.notice')
            ->with('status', 'Confirmez votre adresse e-mail pour accéder à votre compte.');
    }
}
