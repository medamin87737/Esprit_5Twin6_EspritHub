<?php

namespace App\Services\Certifications;

use App\Models\Certification;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Module 5 — Traduction de fiche certification via LibreTranslate (gratuit, sans clé).
 * API publique : https://libretranslate.com
 */
class TraductionService
{
    private const ENDPOINT = 'https://api.mymemory.translated.net/get';

    private const LANGUES = [
        'fr' => 'Français',
        'en' => 'English',
        'ar' => 'العربية',
    ];

    /** @return array<string, string> */
    public function langues(): array
    {
        return self::LANGUES;
    }

    /**
     * Traduit la fiche certification dans la langue cible.
     *
     * @return array<string, string>
     */
    public function traduire(Certification $certification, string $cible): array
    {
        if (! array_key_exists($cible, self::LANGUES)) {
            throw new RuntimeException("Langue non supportée : {$cible}");
        }

        $champs = $this->champsATraduire($certification);

        $traduits = [];
        foreach ($champs as $cle => $texte) {
            $traduits[$cle] = $this->traduireTexte($texte, $cible);
        }

        return $traduits;
    }

    /**
     * @return array<string, string>
     */
    private function champsATraduire(Certification $certification): array
    {
        return [
            'type_label'    => $certification->typeLabel(),
            'statut'        => $certification->statutLabel(),
            'produit'       => $certification->produit?->nom ?? '',
            'categorie'     => $certification->produit?->categorie?->nom ?? '',
            'organisme'     => $certification->organisme?->nom ?? '',
            'pays'          => $certification->organisme?->pays ?? '',
            'accreditation' => $certification->organisme?->accreditation ?? '',
            'origine'       => $certification->produit?->origine ?? '',
        ];
    }

    private function traduireTexte(string $texte, string $cible): string
    {
        if (trim($texte) === '') {
            return '';
        }

        if ($cible === 'fr') {
            return $texte;
        }

        try {
            $url = self::ENDPOINT . '?' . http_build_query([
                'q'        => $texte,
                'langpair' => "fr|{$cible}",
            ]);

            // Utiliser file_get_contents pour éviter les problèmes SSL du Http client Laravel
            $context = stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                'http' => ['timeout' => 10],
            ]);

            $brut = @file_get_contents($url, false, $context);

            if ($brut !== false) {
                $data = json_decode($brut, true);
                $traduit = $data['responseData']['translatedText'] ?? null;
                if (is_string($traduit) && trim($traduit) !== '') {
                    return $traduit;
                }
            }
        } catch (Throwable) {
        }

        return $texte;
    }
}
