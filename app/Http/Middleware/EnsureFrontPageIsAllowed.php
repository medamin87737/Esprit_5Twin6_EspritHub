<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureFrontPageIsAllowed
{
    public function handle(Request $request, Closure $next, string $page): Response
    {
        if (Gate::allows('front', $page)) {
            return $next($request);
        }

        $libelle = config("nutritrace.front.pages.{$page}.libelle", $page);

        if (! $request->user()) {
            return redirect()->guest(route('login'))
                ->with('status', "Connectez-vous pour accéder à la page « {$libelle} ».");
        }

        return redirect()->route('home')
            ->with('status', "La page « {$libelle} » n'est pas disponible pour le profil {$request->user()->roleLabel()}.");
    }
}
