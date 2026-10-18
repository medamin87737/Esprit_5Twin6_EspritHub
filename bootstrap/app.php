<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureProfilSocieteComplet;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

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
        ]);

        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user()?->espaceParDefaut() ?? route('home')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expirée. Rechargez la page.'], 419);
            }

            return redirect()
                ->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'delete_password']))
                ->with('error', 'Votre session a expiré. Rechargez la page puis réessayez.');
        });
    })->create();
