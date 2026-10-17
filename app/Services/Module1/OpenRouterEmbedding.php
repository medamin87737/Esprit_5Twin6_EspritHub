<?php

declare(strict_types=1);

namespace App\Services\Module1;

use App\Services\Module1\Contrats\EmbeddingInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Driver OpenRouter pour les embeddings (Module 1).
 *
 * POST {url}/embeddings { model, input: [textes] }
 * → { data: [{ embedding: float[] }, …] }
 *
 * En cas d'erreur : délègue au RepliLocalEmbedding.
 */
class OpenRouterEmbedding implements EmbeddingInterface
{
    /** Suivi du repli pour le dernier appel. */
    public bool $fallbackUsed = false;

    public function embeddings(array $textes): array
    {
        $this->fallbackUsed = false;
        $cle = config('ia.cle');

        if (empty($cle)) {
            $this->fallbackUsed = true;

            return (new RepliLocalEmbedding)->embeddings($textes);
        }

        try {
            $reponse = Http::timeout((int) config('ia.timeout', 20))
                ->withHeaders([
                    'Authorization' => 'Bearer '.$cle,
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => 'NutriTrace',
                ])
                ->post(rtrim((string) config('ia.openrouter_url'), '/').'/embeddings', [
                    'model' => config('ia.modeles.embeddings'),
                    'input' => $textes,
                ]);

            if (in_array($reponse->status(), [401, 402, 429], true)) {
                Log::warning('Module1 IA3 : erreur '.$reponse->status().' → repli local');
                $this->fallbackUsed = true;

                return (new RepliLocalEmbedding)->embeddings($textes);
            }

            $reponse->throw();

            $data = $reponse->json('data', []);

            return collect($data)
                ->sortBy('index')
                ->map(fn ($item) => $item['embedding'])
                ->all();

        } catch (\Throwable $e) {
            Log::warning('Module1 IA3 exception : '.$e->getMessage());
            $this->fallbackUsed = true;

            return (new RepliLocalEmbedding)->embeddings($textes);
        }
    }

    public function estRepli(): bool
    {
        return empty(config('ia.cle'));
    }
}
