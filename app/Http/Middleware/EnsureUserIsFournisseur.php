<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFournisseur
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isFournisseur(), 403, 'Accès réservé aux fournisseurs.');

        return $next($request);
    }
}
