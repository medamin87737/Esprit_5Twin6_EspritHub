<?php

declare(strict_types=1);

namespace App\Services\Module1;

use App\Services\Module1\Contrats\CategoriseurInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Driver OpenRouter pour la catégorisation IA (Module 1).
 *
 * En cas d'erreur non-récupérable (401, 402, 429, timeout, JSON invalide),
 * délègue au RepliLocalCategoriseur et ajoute un avertissement.
 */
class OpenRouterCategoriseur implements CategoriseurInterface
{
    private RepliLocalCategoriseur $repli;

    public function __construct()
    {
        $this->repli = new RepliLocalCategoriseur;
    }

    /** {@inheritDoc} */
    public function categoriser(
        string $nom,
        string $description,
        string $composition,
        array $categories
    ): array {
        $cle = config('ia.cle');

        if (empty($cle)) {
            return $this->repli->categoriser($nom, $description, $composition, $categories);
        }

        $listeCategories = implode("\n", array_map(
            fn ($c) => "- id:{$c['id']} nom:{$c['nom']} type:{$c['type']}",
            $categories
        ));

        $prompt = <<<PROMPT
            Tu es un assistant de classification alimentaire pour l'application NutriTrace.
            Réponds UNIQUEMENT en JSON valide (pas de texte autour).

            Produit :
            - Nom : {$nom}
            - Description : {$description}
            - Composition : {$composition}

            Catégories disponibles :
            {$listeCategories}

            Retourne un objet JSON avec ces clés exactes :
            {
              "categorie_id": <entier, id de la catégorie la plus appropriée>,
              "type": <string, type de la catégorie choisie>,
              "composition": <string ou null, liste d'ingrédients suggérée>,
              "description": <string ou null, description courte>,
              "confiance": <float entre 0 et 1>,
              "justification": <string, explication courte>
            }
            PROMPT;

        try {
            $reponse = Http::timeout((int) config('ia.timeout', 20))
                ->withHeaders([
                    'Authorization' => 'Bearer '.$cle,
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => 'NutriTrace',
                ])
                ->post(rtrim((string) config('ia.openrouter_url'), '/').'/chat/completions', [
                    'model' => config('ia.modeles.texte'),
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            // Erreurs récupérables → repli
            if (in_array($reponse->status(), [401, 402, 429], true)) {
                Log::warning('Module1 IA1 : erreur '.$reponse->status().' → repli local');

                $resultat = $this->repli->categoriser($nom, $description, $composition, $categories);
                $resultat['avertissement'] = 'Service IA temporairement indisponible (code '.$reponse->status().'). Suggestion locale utilisée.';

                return $resultat;
            }

            $reponse->throw();

            $contenu = $reponse->json('choices.0.message.content', '{}');

            // Tolère une réponse entourée de ```json
            $contenu = preg_replace('/^```json\s*/i', '', (string) $contenu);
            $contenu = preg_replace('/\s*```$/', '', $contenu);

            $donnees = json_decode($contenu, true);

            if (! is_array($donnees)) {
                throw new \RuntimeException('JSON invalide reçu de l\'IA');
            }

            return $this->validerEtEnrichir($donnees, $categories);

        } catch (\Throwable $e) {
            Log::warning('Module1 IA1 exception : '.$e->getMessage());
            $resultat = $this->repli->categoriser($nom, $description, $composition, $categories);
            $resultat['avertissement'] = 'Erreur IA ('.class_basename($e).'). Suggestion locale utilisée.';

            return $resultat;
        }
    }

    /**
     * Valide la réponse de l'IA :
     * - categorie_id doit exister dans la liste ; sinon on prend une catégorie du type proposé.
     *
     * @param  array<string, mixed>  $donnees
     * @param  array<int, array{id:int, nom:string, type:string}>  $categories
     * @return array<string, mixed>
     */
    private function validerEtEnrichir(array $donnees, array $categories): array
    {
        $categorieId = isset($donnees['categorie_id']) ? (int) $donnees['categorie_id'] : null;
        $type = isset($donnees['type']) ? (string) $donnees['type'] : null;

        // Vérifie que l'ID existe
        $catValide = collect($categories)->firstWhere('id', $categorieId);

        if ($catValide === null) {
            // Cherche par type proposé
            $catValide = collect($categories)->firstWhere('type', $type) ?? $categories[0] ?? null;
            $donnees['avertissement'] = 'La catégorie suggérée par l\'IA n\'existait pas ; remplacée par la catégorie de type « '.($type ?? '?').' ».';
        }

        $donnees['categorie_id'] = $catValide['id'] ?? null;
        $donnees['categorie_nom'] = $catValide['nom'] ?? null;
        $donnees['confiance'] = isset($donnees['confiance']) ? (float) $donnees['confiance'] : null;

        return $donnees;
    }
}
