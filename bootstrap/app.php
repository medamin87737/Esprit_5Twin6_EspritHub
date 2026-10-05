<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsFournisseur;
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
            'fournisseur' => EnsureUserIsFournisseur::class,
        ]);

        $middleware->web(append: [
            EnsureAccountIsActive::class,
        ]);

        $middleware->redirectUsersTo(
            fn (Request $request) => match (true) {
                (bool) $request->user()?->isAdmin() => route('admin.dashboard'),
                (bool) $request->user()?->isFournisseur() => route('fournisseur.produits.index'),
                default => route('home'),
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
