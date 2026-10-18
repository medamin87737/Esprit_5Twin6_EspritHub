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
    public function chat(array $messages, float $temperature = 0.2, ?string $model = null, bool $jsonMode = false): array
    {
        if (! $this->configure()) {
            throw new RuntimeException('La clé OPENROUTER_API_KEY est absente du fichier .env.');
        }

        $timeout = (int) config('openrouter.timeout', 20);
        $connectTimeout = (int) config('openrouter.connect_timeout', 8);
        $payload = [
            'model' => $model ?: config('openrouter.model'),
            'temperature' => $temperature,
            'messages' => $this->messagesUtf8($messages),
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $corps = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

        if ($corps === false) {
            throw new RuntimeException('Impossible de préparer la requête IA.');
        }

        try {
            $reponse = Http::baseUrl(rtrim((string) config('openrouter.base_url'), '/'))
                ->withToken((string) config('openrouter.api_key'))
                ->acceptJson()
                ->timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->retry(1, 400, fn ($e) => $e instanceof ConnectionException)
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                        CURLOPT_DNS_CACHE_TIMEOUT => 120,
                    ],
                ])
                ->withHeaders([
                    'HTTP-Referer' => (string) config('openrouter.site_url'),
                    'X-Title' => (string) config('openrouter.site_name'),
                    'Content-Type' => 'application/json',
                ])
                ->withBody($corps, 'application/json')
                ->post('/chat/completions');
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Impossible de joindre OpenRouter (réseau/DNS). Détail : '.$e->getMessage(),
                0,
                $e,
            );
        }

        if ($reponse->failed()) {
            // Certains modèles free refusent response_format → on relance sans.
            if ($jsonMode && in_array($reponse->status(), [400, 422], true)) {
                return $this->chat($messages, $temperature, $model, false);
            }

            $detail = $reponse->json('error.message') ?? $reponse->body();

            if ($reponse->status() === 429) {
                throw new RuntimeException('Limite OpenRouter (429). Attendez 1 minute puis réessayez.');
            }

            throw new RuntimeException('OpenRouter erreur ('.$reponse->status().') : '.$detail);
        }

        $contenu = data_get($reponse->json(), 'choices.0.message.content');

        // Certains modèles mettent le JSON dans un tableau de parts.
        if (! is_string($contenu) || trim($contenu) === '') {
            $parts = data_get($reponse->json(), 'choices.0.message.content');
            if (is_array($parts)) {
                $contenu = collect($parts)->pluck('text')->filter()->implode("\n");
            }
        }

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
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    private function messagesUtf8(array $messages): array
    {
        $nettoyer = function (mixed $valeur) use (&$nettoyer): mixed {
            if (is_string($valeur)) {
                if (! mb_check_encoding($valeur, 'UTF-8') && function_exists('iconv')) {
                    $valeur = (string) (@iconv('UTF-8', 'UTF-8//IGNORE', $valeur) ?: '');
                }
                $json = json_encode($valeur, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
                $decoded = is_string($json) ? json_decode($json, true) : null;

                return is_string($decoded) ? $decoded : $valeur;
            }

            return is_array($valeur) ? array_map($nettoyer, $valeur) : $valeur;
        };

        return array_map($nettoyer, $messages);
    }

    /**
     * @return array<string, mixed>
     */
    public function decoderJson(string $contenu): array
    {
        $brut = trim($contenu);

        // Enlever markdown ```json ... ```
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $brut, $m)) {
            $brut = trim($m[1]);
        }

        // Extraire le premier objet { ... } équilibré
        $debut = strpos($brut, '{');
        if ($debut !== false) {
            $profondeur = 0;
            $fin = null;
            $len = strlen($brut);
            for ($i = $debut; $i < $len; $i++) {
                $c = $brut[$i];
                if ($c === '{') {
                    $profondeur++;
                } elseif ($c === '}') {
                    $profondeur--;
                    if ($profondeur === 0) {
                        $fin = $i;
                        break;
                    }
                }
            }
            if ($fin !== null) {
                $brut = substr($brut, $debut, $fin - $debut + 1);
            }
        }

        // Corrections fréquentes des modèles free
        $brut = preg_replace("/,\s*([}\]])/", '$1', $brut) ?? $brut;
        $brut = str_replace(["\u201c", "\u201d", "\u2018", "\u2019"], ['"', '"', "'", "'"], $brut);

        $data = json_decode($brut, true);

        if (! is_array($data)) {
            throw new RuntimeException(
                'La réponse IA n\'est pas un JSON valide. Aperçu : '.mb_substr(trim($contenu), 0, 180)
            );
        }

        return $data;
    }
}
