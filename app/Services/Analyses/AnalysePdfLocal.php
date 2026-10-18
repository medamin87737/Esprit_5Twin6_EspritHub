<?php

namespace App\Services\Analyses;

/**
 * Analyse qualité 100 % locale à partir du texte (sans OpenRouter).
 */
class AnalysePdfLocal
{
    /**
     * @return array{type: ?string, resultat: ?string, date_prelevement: ?string, date_resultat: ?string, commentaire: ?string, confiance: float, resume_court: string, titre_resume: string, resume_qualite: string}
     */
    public function analyser(string $texte, string $nomFichier = 'rapport.txt'): array
    {
        $types = array_keys(config('nutritrace.options.analyse_types', []));
        $bas = mb_strtolower($texte);

        $type = $this->detecterType($bas, $types);
        $resultat = $this->detecterResultat($bas);
        $dates = $this->detecterDates($texte);
        $indices = $this->indicesQualite($bas);

        $typeLabel = $type
            ? (config('nutritrace.options.analyse_types.'.$type) ?? str_replace('_', ' ', $type))
            : 'non précisé';

        $resultatLabel = match ($resultat) {
            'conforme' => '**conforme**',
            'non_conforme' => '**non conforme**',
            default => '**en attente**',
        };

        $titre = match ($resultat) {
            'conforme' => 'Rapport conforme — synthèse qualité',
            'non_conforme' => 'Alerte qualité — non-conformité',
            default => 'Synthèse qualité du rapport',
        };

        $points = $indices === []
            ? 'Aucun contaminant critique n\'a été identifié dans le rapport.'
            : 'Indicateurs identifiés : **'.implode('**, **', array_slice($indices, 0, 5)).'**.';

        $reco = match ($resultat) {
            'conforme' => 'Action recommandée : archiver le rapport et maintenir le contrôle de routine.',
            'non_conforme' => 'Action recommandée : **suspendre le lot** et programmer une contre-analyse laboratoire.',
            default => 'Action recommandée : compléter le verdict laboratoire dès réception des résultats.',
        };

        $commentaire = match ($resultat) {
            'conforme' => "Résultat conforme pour l'analyse {$typeLabel}.",
            'non_conforme' => "Résultat non conforme pour l'analyse {$typeLabel}.",
            default => "Résultat en attente pour l'analyse {$typeLabel}.",
        };

        if ($indices !== []) {
            $commentaire .= ' Indicateurs : '.implode(', ', array_slice($indices, 0, 4)).'.';
        }

        $resumeQualite = "Synthèse du rapport **{$nomFichier}**. "
            ."Nature d'analyse : **{$typeLabel}**. Verdict : {$resultatLabel}. "
            .($dates['prelevement'] ? "Date de prélèvement : **{$dates['prelevement']}**. " : '')
            .($dates['resultat'] ? "Date de résultat : **{$dates['resultat']}**. " : '')
            ."{$points} {$reco}";

        return [
            'type' => $type,
            'resultat' => $resultat,
            'date_prelevement' => $dates['prelevement'],
            'date_resultat' => $dates['resultat'],
            'commentaire' => mb_substr($commentaire, 0, 400),
            'confiance' => $this->confiance($type, $resultat, $dates, $indices),
            'resume_court' => 'Le rapport indique un résultat '
                .str_replace(['**', '_'], '', $resultatLabel)
                ." ({$typeLabel}).",
            'titre_resume' => $titre,
            'resume_qualite' => $resumeQualite,
        ];
    }

    /**
     * @param  list<string>  $types
     */
    private function detecterType(string $bas, array $types): ?string
    {
        $aliases = [
            'microbiologique' => ['microbio', 'salmonella', 'listeria', 'e. coli', 'coli', 'germes', 'bactérie'],
            'physico_chimique' => ['physico', 'chimique', 'humidité', 'pesticide', 'nitrate', 'ph '],
            'sensorielle' => ['sensoriel', 'organoleptique', 'odeur', 'goût', 'texture'],
            'nutritionnelle' => ['nutrition', 'protéine', 'lipide', 'glucide', 'calor'],
        ];

        foreach ($aliases as $code => $mots) {
            if (! in_array($code, $types, true)) {
                continue;
            }
            foreach ($mots as $mot) {
                if (str_contains($bas, $mot)) {
                    return $code;
                }
            }
        }

        return $types[0] ?? null;
    }

    private function detecterResultat(string $bas): string
    {
        if (preg_match('/\b(non[-\s]?conforme|hors norme|dépassement|contamin)/u', $bas)) {
            return 'non_conforme';
        }
        if (preg_match('/\b(conforme|satisfaisant|dans les normes|non d[ée]tect)/u', $bas)) {
            return 'conforme';
        }

        return 'en_attente';
    }

    /**
     * @return array{prelevement: ?string, resultat: ?string}
     */
    private function detecterDates(string $texte): array
    {
        $trouvees = [];

        if (preg_match_all('/\b(\d{2})[\/\-.](\d{2})[\/\-.](\d{4})\b/', $texte, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                if (checkdate((int) $hit[2], (int) $hit[1], (int) $hit[3])) {
                    $trouvees[] = sprintf('%s-%s-%s', $hit[3], $hit[2], $hit[1]);
                }
            }
        }

        if (preg_match_all('/\b(\d{4})-(\d{2})-(\d{2})\b/', $texte, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                if (checkdate((int) $hit[2], (int) $hit[3], (int) $hit[1])) {
                    $trouvees[] = sprintf('%s-%s-%s', $hit[1], $hit[2], $hit[3]);
                }
            }
        }

        $trouvees = array_values(array_unique($trouvees));

        return [
            'prelevement' => $trouvees[0] ?? null,
            'resultat' => $trouvees[1] ?? ($trouvees[0] ?? null),
        ];
    }

    /**
     * @return list<string>
     */
    private function indicesQualite(string $bas): array
    {
        $carte = [
            'Salmonella' => 'salmonella',
            'Listeria' => 'listeria',
            'E. coli' => 'e\. coli|escherichia',
            'pesticides' => 'pesticide|résidu',
            'mycotoxines' => 'mycotoxine|aflatoxine',
            'humidité' => 'humidité',
        ];

        $out = [];
        foreach ($carte as $label => $pattern) {
            if (preg_match('/'.$pattern.'/u', $bas)) {
                $out[] = $label;
            }
        }

        return $out;
    }

    /**
     * @param  array{prelevement: ?string, resultat: ?string}  $dates
     * @param  list<string>  $indices
     */
    private function confiance(?string $type, string $resultat, array $dates, array $indices): float
    {
        $score = 0.4;
        if ($type) {
            $score += 0.15;
        }
        if ($resultat !== 'en_attente') {
            $score += 0.25;
        }
        if ($dates['prelevement']) {
            $score += 0.1;
        }
        if ($indices !== []) {
            $score += 0.1;
        }

        return round(min(0.9, $score), 2);
    }
}
