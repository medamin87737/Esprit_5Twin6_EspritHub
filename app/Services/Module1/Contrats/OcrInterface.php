<?php

declare(strict_types=1);

namespace App\Services\Module1\Contrats;

use Illuminate\Http\UploadedFile;

/**
 * Interface du service OCR IA (Module 1).
 *
 * Permet de remplacer OpenRouter par Google Cloud Vision ou autre
 * via l'environnement, sans modifier les contrôleurs.
 */
interface OcrInterface
{
    /**
     * Lit une étiquette produit depuis une image.
     *
     * @return array{
     *     texte: string|null,
     *     nom: string|null,
     *     code_barres: string|null,
     *     code_barres_valide: bool,
     *     code_barres_avertissement: string|null,
     *     origine: string|null,
     *     composition: string|null,
     *     description: string|null,
     * }
     *
     * @throws \RuntimeException si la clé API est absente (HTTP 503 attendu par le contrôleur)
     */
    public function lire(UploadedFile $image): array;
}
