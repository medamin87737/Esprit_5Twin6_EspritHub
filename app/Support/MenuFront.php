<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Menu du Front Office propre au profil connecté (config nutritrace.menus).
 */
class MenuFront
{
    public static function profil(?User $user): string
    {
        return match (true) {
            $user === null => 'visiteur',
            $user->isConsommateur() => 'consommateur',
            $user->isPro() => 'pro',
            default => 'visiteur',
        };
    }

    /**
     * @return list<array{route: string, actif: list<string>, libelle: string, icone: string, droit: ?string}>
     */
    public static function liens(?User $user): array
    {
        return array_values(array_filter(
            config('nutritrace.menus.'.self::profil($user)),
            fn (array $lien) => $lien['droit'] === null || Gate::forUser($user)->allows($lien['droit']),
        ));
    }

    /**
     * Destination du logo : l'espace de travail pour un professionnel, l'accueil sinon.
     */
    public static function accueil(?User $user): string
    {
        return $user?->isPro() ? route('pro.dashboard') : route('home');
    }
}
