<?php

namespace App\Policies;

use App\Models\Produit;
use App\Models\User;

class ProduitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isFournisseur();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isFournisseur();
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
     * L'administrateur gère tout le catalogue, un fournisseur uniquement ses produits.
     */
    private function gere(User $user, Produit $produit): bool
    {
        return $user->isAdmin() || ($user->isFournisseur() && (int) $produit->fournisseur_id === (int) $user->id);
    }
}
