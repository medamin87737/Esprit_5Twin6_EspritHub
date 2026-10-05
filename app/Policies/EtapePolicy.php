<?php

namespace App\Policies;

use App\Models\Etape;
use App\Models\User;

/**
 * Une étape appartient à l'acteur qui l'a saisie et reste modifiable
 * tant qu'il détient le lot (verrouillée après le transfert).
 */
class EtapePolicy
{
    public function update(User $user, Etape $etape): bool
    {
        return $this->gere($user, $etape);
    }

    public function delete(User $user, Etape $etape): bool
    {
        return $this->gere($user, $etape);
    }

    public function ajouterIndicateur(User $user, Etape $etape): bool
    {
        return $this->gere($user, $etape);
    }

    private function gere(User $user, Etape $etape): bool
    {
        $acteur = $user->isPro() ? $user->acteur : null;

        return $acteur !== null
            && (int) $etape->acteur_id === (int) $acteur->id
            && ! $etape->estVerrouillee();
    }
}
