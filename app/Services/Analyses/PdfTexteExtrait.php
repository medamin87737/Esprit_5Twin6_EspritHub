<?php

namespace App\Services\Analyses;

use App\Models\Analyse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Extraction texte locale : TXT / DOCX uniquement.
 */
class PdfTexteExtrait
{
    /**
     * @return array{texte: string, pages: int, source: string}
     */
    public function depuis(UploadedFile|string $fichier, ?string $disque = null): array
    {
        [$binaire, $extension, $nom] = $this->charger($fichier, $disque);

        if (strlen($binaire) > 5 * 1024 * 1024) {
            throw new RuntimeException('Le fichier dépasse 5 Mo.');
        }

        $texte = match ($extension) {
            'txt', 'text', 'md', 'csv' => $this->depuisTxt($binaire),
            'docx' => $this->depuisDocx($binaire),
            default => throw new RuntimeException(
                'Format non supporté ('.$extension.'). Utilisez un fichier .txt ou .docx.'
            ),
        };

        $texte = $this->nettoyer($texte);

        if ($texte === '' || mb_strlen($texte) < 20) {
            throw new RuntimeException(
                'Aucun texte lisible dans « '.$nom.' ». Utilisez un .txt ou .docx avec du contenu.'
            );
        }

        if (mb_strlen($texte) > 6000) {
            $texte = mb_substr($texte, 0, 6000)."\n\n[…]";
        }

        return [
            'texte' => $texte,
            'pages' => max(1, (int) ceil(mb_strlen($texte) / 3000)),
            'source' => $extension,
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function charger(UploadedFile|string $fichier, ?string $disque): array
    {
        if ($fichier instanceof UploadedFile) {
            $nom = $fichier->getClientOriginalName() ?: 'rapport.txt';
            $extension = strtolower($fichier->getClientOriginalExtension() ?: pathinfo($nom, PATHINFO_EXTENSION));

            return [(string) $fichier->get(), $extension, $nom];
        }

        $disque ??= Analyse::DISQUE;
        if (! Storage::disk($disque)->exists($fichier)) {
            throw new RuntimeException('Le rapport est introuvable sur le serveur.');
        }

        $nom = basename($fichier);
        $extension = strtolower(pathinfo($nom, PATHINFO_EXTENSION) ?: 'txt');

        return [(string) Storage::disk($disque)->get($fichier), $extension, $nom];
    }

    private function depuisTxt(string $binaire): string
    {
        return $this->versUtf8($binaire);
    }

    private function depuisDocx(string $binaire): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'nt-docx-');
        if ($tmp === false) {
            throw new RuntimeException('Impossible de lire le DOCX.');
        }

        try {
            file_put_contents($tmp, $binaire);
            $zip = new ZipArchive;
            if ($zip->open($tmp) !== true) {
                throw new RuntimeException('DOCX invalide.');
            }
            $xml = $zip->getFromName('word/document.xml') ?: '';
            $zip->close();
        } finally {
            @unlink($tmp);
        }

        if ($xml === '') {
            throw new RuntimeException('DOCX sans texte.');
        }

        $xml = str_replace(['</w:p>', '</w:tr>', '<w:tab/>'], ["\n", "\n", "\t"], $xml);
        $texte = strip_tags($xml);

        return html_entity_decode($texte, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function nettoyer(string $texte): string
    {
        $texte = $this->versUtf8($texte);
        $texte = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texte) ?? $texte;
        $texte = preg_replace("/[ \t]+/u", ' ', $texte) ?? $texte;
        $texte = preg_replace("/\n{3,}/u", "\n\n", $texte) ?? $texte;

        return trim($texte);
    }

    private function versUtf8(string $valeur): string
    {
        if ($valeur === '') {
            return '';
        }

        if (! mb_check_encoding($valeur, 'UTF-8')) {
            foreach (['Windows-1252', 'ISO-8859-1'] as $from) {
                $converti = @mb_convert_encoding($valeur, 'UTF-8', $from);
                if (is_string($converti) && mb_check_encoding($converti, 'UTF-8')) {
                    $valeur = $converti;
                    break;
                }
            }
        }

        if (function_exists('iconv')) {
            $propre = @iconv('UTF-8', 'UTF-8//IGNORE', $valeur);
            if (is_string($propre)) {
                $valeur = $propre;
            }
        }

        $json = json_encode($valeur, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (is_string($decoded)) {
                $valeur = $decoded;
            }
        }

        return $valeur;
    }
}
