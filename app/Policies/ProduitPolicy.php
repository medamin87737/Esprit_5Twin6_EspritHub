<?php

namespace App\Policies;

use App\Models\Produit;
use App\Models\User;

class ProduitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $this->gereProduits($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $this->gereProduits($user);
    }

    public function view(User $user, Produit $produit): bool
    {
        return $this->gere($user, $produit);
    }

    public function update(User $user, Produit $produit): bool
    {
        return $this->gere($user, $produit);
    }

    public function delete(User $user, Produit $produit): bool
    {
        return $this->gere($user, $produit);
    }

    /**
     * L'administrateur gère tout le catalogue ; un producteur ou un
     * transformateur uniquement les produits dont il est propriétaire.
     */
    private function gere(User $user, Produit $produit): bool
    {
        return $user->isAdmin()
            || ($this->gereProduits($user) && (int) $produit->proprietaire_id === (int) $user->id);
    }

    private function gereProduits(User $user): bool
    {
        return (bool) $user->droitPro('gere_produits');
    }
}
