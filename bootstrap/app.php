<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureEmailIsVerifiedWhenAuthenticated;
use App\Http\Middleware\EnsureProfilSocieteComplet;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'profil-societe' => EnsureProfilSocieteComplet::class,
        ]);

        $middleware->web(append: [
            EnsureAccountIsActive::class,
            EnsureEmailIsVerifiedWhenAuthenticated::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user && ! $user->hasVerifiedEmail()) {
                return route('verification.notice');
            }

            return $user?->espaceParDefaut() ?? route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Anti « 419 | PAGE EXPIRED » sur TOUTES les routes.
        // Laravel convertit TokenMismatchException en HttpException(419) AVANT
        // les callbacks : il faut donc intercepter HttpExceptionInterface + code 419
        // (un callback typé TokenMismatchException ne se déclenche jamais).
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expirée. Rechargez la page.'], 419);
            }

            // Déconnexion : même avec un jeton expiré, on déconnecte proprement
            // au lieu d'afficher la page 419.
            if ($request->routeIs('logout')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('home');
            }

            return redirect()
                ->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'delete_password']))
                ->with('error', 'Votre session a expiré. La page a été rechargée : vous pouvez réessayer.');
        });
    })->create();
