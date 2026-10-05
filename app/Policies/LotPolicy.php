<?php

namespace App\Policies;

use App\Models\Lot;
use App\Models\User;

/**
 * Lots dans l'espace pro : un acteur ne voit que les lots qu'il détient ou
 * sur lesquels il a travaillé, et n'agit que sur ceux qu'il détient.
 */
class LotPolicy
{
    public function view(User $user, Lot $lot): bool
    {
        $acteur = $user->isPro() ? $user->acteur : null;

        return $acteur !== null
            && ($lot->estChez($acteur) || $lot->etapes()->where('acteur_id', $acteur->id)->exists());
    }

    public function ajouterEtape(User $user, Lot $lot): bool
    {
        return $user->isPro() && $lot->estChez($user->acteur) && ! empty($user->droitPro('etapes'));
    }

    public function ajouterAnalyse(User $user, Lot $lot): bool
    {
        return $user->isPro() && $lot->estChez($user->acteur);
    }

    public function transferer(User $user, Lot $lot): bool
    {
        return $user->isPro() && $lot->estChez($user->acteur) && $user->rolesEnAval() !== [];
    }
}
