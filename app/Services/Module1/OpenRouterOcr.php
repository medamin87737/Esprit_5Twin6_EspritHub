<?php

declare(strict_types=1);

namespace App\Services\Module1;

use App\Services\Module1\Contrats\OcrInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Driver OpenRouter pour l'OCR d'étiquettes produit (Module 1).
 *
 * Envoie l'image en base64 via l'API vision (message image_url).
 * Sans clé API : lève une RuntimeException (→ HTTP 503 dans le contrôleur).
 */
class OpenRouterOcr implements OcrInterface
{
    /** {@inheritDoc} */
    public function lire(UploadedFile $image): array
    {
        $cle = config('ia.cle');

        if (empty($cle)) {
            throw new \RuntimeException('Clé API IA non configurée. Contactez l\'administrateur.');
        }

        $mime = $image->getMimeType() ?? 'image/jpeg';
        $base64 = base64_encode(file_get_contents($image->getRealPath()));
        $dataUrl = "data:{$mime};base64,{$base64}";

        $prompt = <<<'PROMPT'
            Lis cette étiquette de produit alimentaire et retourne UNIQUEMENT un objet JSON valide.
            Clés attendues (null si non trouvé) :
            {
              "texte": "<texte brut complet de l'étiquette>",
              "nom": "<nom du produit>",
              "code_barres": "<code-barres EAN-13, 13 chiffres uniquement>",
              "origine": "<pays ou région d'origine>",
              "composition": "<liste des ingrédients>",
              "description": "<description courte du produit>"
            }
            PROMPT;

        $reponse = Http::timeout((int) config('ia.timeout', 20))
            ->withHeaders([
                'Authorization' => 'Bearer '.$cle,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'NutriTrace',
            ])
            ->post(rtrim((string) config('ia.openrouter_url'), '/').'/chat/completions', [
                'model' => config('ia.modeles.vision'),
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text',      'text' => $prompt],
                        ['type' => 'image_url',  'image_url' => ['url' => $dataUrl]],
                    ],
                ]],
            ]);

        $reponse->throw();

        $contenu = $reponse->json('choices.0.message.content', '{}');

        // Tolère ```json
        $contenu = preg_replace('/^```json\s*/i', '', (string) $contenu);
        $contenu = preg_replace('/\s*```$/', '', $contenu);

        $donnees = json_decode($contenu, true);

        if (! is_array($donnees)) {
            throw new \RuntimeException('JSON invalide reçu du modèle vision.');
        }

        // Vérification EAN-13
        $codeBarres = isset($donnees['code_barres']) ? preg_replace('/\D/', '', (string) $donnees['code_barres']) : null;
        $codeBarresValide = false;
        $codeBarresAvert = null;

        if ($codeBarres !== null && strlen($codeBarres) === 13) {
            $codeBarresValide = self::verifierEan13($codeBarres);
            if (! $codeBarresValide) {
                $codeBarresAvert = 'Le code-barres lu ('.$codeBarres.') ne passe pas la vérification EAN-13. Vérifiez-le manuellement.';
            }
        } elseif ($codeBarres !== null) {
            $codeBarresAvert = 'Code-barres lu ('.$codeBarres.') invalide : doit contenir exactement 13 chiffres.';
            $codeBarresValide = false;
        }

        return [
            'texte' => $donnees['texte'] ?? null,
            'nom' => $donnees['nom'] ?? null,
            'code_barres' => $codeBarres,
            'code_barres_valide' => $codeBarresValide,
            'code_barres_avertissement' => $codeBarresAvert,
            'origine' => $donnees['origine'] ?? null,
            'composition' => $donnees['composition'] ?? null,
            'description' => $donnees['description'] ?? null,
        ];
    }

    /**
     * Vérification de la clé de contrôle EAN-13.
     * Somme pondérée (×1 positions impaires, ×3 positions paires) mod 10 = 0.
     */
    public static function verifierEan13(string $code): bool
    {
        if (! preg_match('/^\d{13}$/', $code)) {
            return false;
        }

        $somme = 0;
        for ($i = 0; $i < 12; $i++) {
            $somme += (int) $code[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        $cleControle = (10 - ($somme % 10)) % 10;

        return $cleControle === (int) $code[12];
    }
}
