<?php

namespace App\Policies;

use App\Models\Analyse;
use App\Models\User;

/**
 * Analyses dans l'espace pro : seul le déclarant peut les modifier, et seulement
 * tant que le lot est en sa possession. Le rapport PDF est réservé aux comptes connectés.
 */
class AnalysePolicy
{
    public function update(User $user, Analyse $analyse): bool
    {
        return $user->isPro()
            && (int) $analyse->user_id === (int) $user->id
            && $analyse->lot?->estChez($user->acteur);
    }

    public function delete(User $user, Analyse $analyse): bool
    {
        return $this->update($user, $analyse);
    }

    public function telechargerRapport(User $user, Analyse $analyse): bool
    {
        return $analyse->rapport !== null;
    }
}
