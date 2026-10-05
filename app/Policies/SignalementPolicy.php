<?php

namespace App\Policies;

use App\Models\Signalement;
use App\Models\User;

/**
 * Un consommateur modifie ou supprime uniquement ses signalements encore en attente.
 */
class SignalementPolicy
{
    public function update(User $user, Signalement $signalement): bool
    {
        return $this->gere($user, $signalement);
    }

    public function delete(User $user, Signalement $signalement): bool
    {
        return $this->gere($user, $signalement);
    }

    private function gere(User $user, Signalement $signalement): bool
    {
        return $user->isConsommateur()
            && (int) $signalement->user_id === (int) $user->id
            && $signalement->estEnAttente();
    }
}
