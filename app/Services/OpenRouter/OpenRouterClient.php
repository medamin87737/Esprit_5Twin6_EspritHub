<?php

namespace App\Services\OpenRouter;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client HTTP minimal pour l'API Chat Completions d'OpenRouter.
 */
class OpenRouterClient
{
    public function configure(): bool
    {
        return filled(config('openrouter.api_key'));
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    public function chat(array $messages, float $temperature = 0.2): array
    {
        if (! $this->configure()) {
            throw new RuntimeException('La clé OPENROUTER_API_KEY est absente du fichier .env.');
        }

        try {
            $reponse = Http::baseUrl(rtrim((string) config('openrouter.base_url'), '/'))
                ->withToken((string) config('openrouter.api_key'))
                ->acceptJson()
                ->timeout((int) config('openrouter.timeout', 60))
                ->withHeaders([
                    'HTTP-Referer' => (string) config('openrouter.site_url'),
                    'X-Title' => (string) config('openrouter.site_name'),
                ])
                ->post('/chat/completions', [
                    'model' => config('openrouter.model'),
                    'temperature' => $temperature,
                    'messages' => $messages,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Impossible de joindre OpenRouter : '.$e->getMessage(), 0, $e);
        }

        if ($reponse->failed()) {
            $detail = $reponse->json('error.message') ?? $reponse->body();

            throw new RuntimeException('OpenRouter a renvoyé une erreur ('.$reponse->status().') : '.$detail);
        }

        $contenu = data_get($reponse->json(), 'choices.0.message.content');

        if (! is_string($contenu) || trim($contenu) === '') {
            throw new RuntimeException('OpenRouter a renvoyé une réponse vide.');
        }

        return [
            'content' => trim($contenu),
            'model' => $reponse->json('model'),
            'usage' => $reponse->json('usage'),
        ];
    }

    /**
     * Décode un bloc JSON éventuellement entouré de ```json … ```.
     *
     * @return array<string, mixed>
     */
    public function decoderJson(string $contenu): array
    {
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $contenu, $m)) {
            $contenu = $m[1];
        } elseif (preg_match('/\{.*\}/s', $contenu, $m)) {
            $contenu = $m[0];
        }

        $data = json_decode($contenu, true);

        if (! is_array($data)) {
            throw new RuntimeException('La réponse IA n\'est pas un JSON valide.');
        }

        return $data;
    }
}
