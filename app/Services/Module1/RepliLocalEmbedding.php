<?php

declare(strict_types=1);

namespace App\Services\Module1;

use App\Services\Module1\Contrats\EmbeddingInterface;

/**
 * Repli local pour les embeddings (Module 1).
 *
 * Utilise une approche TF (term frequency) simple :
 *  - Tokenisation par mots (minuscules, sans accents)
 *  - Vecteur de fréquences sur le vocabulaire commun des textes fournis
 *  - Similarité cosinus entre vecteurs
 *
 * Suffisant pour un repli sans clé API : les produits au vocabulaire
 * proche auront une similarité élevée.
 */
class RepliLocalEmbedding implements EmbeddingInterface
{
    public function embeddings(array $textes): array
    {
        // Construit le vocabulaire commun
        $vocabulaire = [];
        $tokenises = [];

        foreach ($textes as $texte) {
            $tokens = $this->tokeniser($texte);
            $tokenises[] = $tokens;
            foreach ($tokens as $t) {
                $vocabulaire[$t] = true;
            }
        }

        $mots = array_keys($vocabulaire);
        $vecteurs = [];

        foreach ($tokenises as $tokens) {
            $freqs = array_fill_keys($mots, 0.0);
            $total = max(count($tokens), 1);
            foreach ($tokens as $t) {
                $freqs[$t] += 1.0 / $total;
            }
            $vecteurs[] = array_values($freqs);
        }

        return $vecteurs;
    }

    public function estRepli(): bool
    {
        return true;
    }

    /**
     * Tokenise une chaîne : minuscules, sans accents, mots ≥ 3 caractères.
     *
     * @return string[]
     */
    private function tokeniser(string $texte): array
    {
        $nettoye = mb_strtolower(trim($texte));
        $nettoye = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $nettoye) ?: $nettoye;
        $nettoye = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $nettoye) ?: $nettoye;
        $mots = preg_split('/\s+/', $nettoye) ?: [];

        return array_values(array_filter($mots, fn ($m) => mb_strlen($m) >= 3));
    }

    /**
     * Similarité cosinus entre deux vecteurs.
     */
    public static function cosinus(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $denom = sqrt($normA) * sqrt($normB);

        return $denom > 0 ? $dot / $denom : 0.0;
    }
}
