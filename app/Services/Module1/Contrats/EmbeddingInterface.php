<?php

declare(strict_types=1);

namespace App\Services\Module1\Contrats;

/**
 * Interface du service d'embeddings IA (Module 1).
 *
 * Permet de remplacer OpenRouter par un autre fournisseur
 * (Google, Hugging Face…) via l'environnement.
 */
interface EmbeddingInterface
{
    /**
     * Calcule les vecteurs d'embedding pour une liste de textes.
     *
     * @param  string[]  $textes
     * @return float[][] vecteurs (même ordre que $textes)
     */
    public function embeddings(array $textes): array;

    /**
     * Indique si le service utilise un vrai modèle IA ou un repli local.
     */
    public function estRepli(): bool;
}
