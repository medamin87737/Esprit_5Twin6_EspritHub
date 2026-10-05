<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureProfilSocieteComplet;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        //
    })->create();
