<?php

declare(strict_types=1);

namespace App\Services\Module1;

use App\Services\Module1\Contrats\CategoriseurInterface;

/**
 * Repli local par mots-clés — utilisé sans clé API ou en cas d'erreur.
 *
 * Règle de pondération : le premier mot du nom compte triple.
 * Ex. « Yaourt à la fraise » → premier mot « Yaourt » → laitiers.
 * Ex. « Jus d'orange »      → premier mot « Jus »    → boissons.
 */
class RepliLocalCategoriseur implements CategoriseurInterface
{
    /** @var array<string, string[]> mots-clés par type de catégorie */
    private const MOTS_CLES = [
        'fruits' => ['pomme', 'poire', 'abricot', 'cerise', 'fraise', 'framboise', 'peche', 'prune', 'raisin', 'orange', 'citron', 'mandarine', 'ananas', 'mangue', 'banane', 'melon', 'pastèque', 'figue', 'datte', 'fruit'],
        'legumes' => ['tomate', 'carotte', 'courgette', 'aubergine', 'poivron', 'concombre', 'laitue', 'salade', 'epinard', 'chou', 'brocoli', 'navet', 'radis', 'oignon', 'ail', 'poireau', 'celeri', 'fenouil', 'artichaut', 'legume'],
        'laitiers' => ['lait', 'yaourt', 'fromage', 'beurre', 'crème', 'creme', 'laitage', 'yogourt', 'kefir', 'mozzarella', 'ricotta', 'camembert', 'brie', 'gouda', 'edam', 'feta'],
        'viandes' => ['viande', 'boeuf', 'veau', 'agneau', 'porc', 'poulet', 'dinde', 'canard', 'lapin', 'merguez', 'saucisse', 'jambon', 'steak', 'filet', 'escalope', 'côtelette', 'cotelette'],
        'cereales' => ['pain', 'farine', 'riz', 'blé', 'ble', 'semoule', 'orge', 'avoine', 'mais', 'maïs', 'céréale', 'cereale', 'pâte', 'pate', 'couscous', 'biscuit', 'gâteau', 'gateau', 'brioche'],
        'boissons' => ['jus', 'eau', 'lait', 'thé', 'the', 'café', 'cafe', 'sirop', 'boisson', 'soda', 'limonade', 'nectar', 'smoothie', 'infusion'],
        'autres' => [],
    ];

    /**
     * Compositions typiques par type de catégorie — utilisées quand
     * le champ composition est vide.
     *
     * @var array<string, string>
     */
    private const COMPOSITIONS_TYPES = [
        'fruits' => 'Fruit frais',
        'legumes' => 'Légume frais',
        'laitiers' => 'Lait pasteurisé, ferments lactiques',
        'viandes' => 'Viande fraîche',
        'cereales' => 'Farine de blé, eau, sel',
        'boissons' => 'Eau, extrait naturel',
        'autres' => 'Ingrédients naturels',
    ];

    /** {@inheritDoc} */
    public function categoriser(
        string $nom,
        string $description,
        string $composition,
        array $categories
    ): array {
        $texte = mb_strtolower($nom.' '.$description.' '.$composition);

        // Extraction du premier mot du nom (pondération ×3)
        $premierMot = mb_strtolower(trim(explode(' ', trim($nom))[0] ?? ''));

        $scores = [];
        foreach (self::MOTS_CLES as $type => $mots) {
            $scores[$type] = 0;
            foreach ($mots as $mot) {
                $occ = substr_count($texte, $mot);
                if ($occ > 0) {
                    $scores[$type] += $occ;
                    // Bonus si le mot est le premier mot du nom
                    if ($premierMot !== '' && str_starts_with($premierMot, $mot)) {
                        $scores[$type] += 3;
                    }
                }
            }
        }

        arsort($scores);
        $meilleurType = (string) array_key_first($scores);
        $maxScore = (int) reset($scores);

        if ($maxScore === 0) {
            $meilleurType = 'autres';
        }

        // Cherche une catégorie du bon type parmi celles disponibles
        $categorieMatch = null;
        foreach ($categories as $cat) {
            if (($cat['type'] ?? '') === $meilleurType) {
                $categorieMatch = $cat;
                break;
            }
        }

        // Fallback : première catégorie disponible
        if ($categorieMatch === null && ! empty($categories)) {
            $categorieMatch = $categories[0];
        }

        return [
            'categorie_id' => $categorieMatch['id'] ?? null,
            'categorie_nom' => $categorieMatch['nom'] ?? null,
            'type' => $meilleurType,
            'composition' => ! empty($composition)
                ? $composition
                : (self::COMPOSITIONS_TYPES[$meilleurType] ?? 'Ingrédients naturels'),
            'description' => ! empty($description)
                ? $description
                : $nom.' — produit '.($categorieMatch['nom'] ?? $meilleurType).'.',
            'confiance' => $maxScore > 0 ? min(0.6, $maxScore * 0.1) : 0.1,
            'justification' => 'Suggestion locale par mots-clés (type détecté : '.$meilleurType.').',
            'avertissement' => 'Suggestion générée localement (IA indisponible). Vérifiez les champs proposés.',
        ];
    }
}
