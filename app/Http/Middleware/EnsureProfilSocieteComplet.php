<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les lots et les étapes sont rattachés à une fiche acteur : un professionnel
 * doit d'abord compléter son profil société.
 */
class EnsureProfilSocieteComplet
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->acteur === null) {
            return redirect()->route('pro.profil.edit')
                ->with('error', 'Complétez d\'abord votre profil société pour gérer vos lots.');
        }

        return $next($request);
    }
}
