<?php

declare(strict_types=1);

namespace App\Services\Module1\Contrats;

/**
 * Interface du service de catégorisation IA (Module 1).
 *
 * Permet de remplacer OpenRouter par un autre fournisseur (Google Cloud,
 * Hugging Face…) via la variable d'environnement, sans modifier les contrôleurs.
 */
interface CategoriseurInterface
{
    /**
     * Propose une catégorie et une composition pour un produit.
     *
     * @param  array<int, array{id:int, nom:string, type:string}>  $categories  Liste des catégories disponibles
     * @return array{
     *     categorie_id: int|null,
     *     type: string|null,
     *     composition: string|null,
     *     description: string|null,
     *     confiance: float|null,
     *     justification: string|null,
     *     avertissement: string|null,
     * }
     */
    public function categoriser(
        string $nom,
        string $description,
        string $composition,
        array $categories
    ): array;
}
