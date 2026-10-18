<?php

namespace App\Services\Analyses;

use App\Models\Analyse;
use App\Models\Lot;
use App\Services\OpenRouter\OpenRouterClient;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

/**
 * Module 2 IA : lit le texte localement, appelle OpenRouter si le réseau répond,
 * sinon analyse locale automatique (pas de blocage DNS/timeout).
 */
class AnalyseIaService
{
    public function __construct(
        private OpenRouterClient $client,
        private PdfTexteExtrait $texteExtrait,
        private AnalysePdfLocal $local,
    ) {}

    public function disponible(): bool
    {
        return true;
    }

    /**
     * @return array{type: ?string, resultat: ?string, date_prelevement: ?string, date_resultat: ?string, commentaire: ?string, confiance: ?float, resume_court: ?string, titre_resume: ?string, resume_qualite: ?string}
     */
    public function extraireDepuisPdf(UploadedFile|string $fichier, ?string $nomFichier = null): array
    {
        $nom = $nomFichier
            ?: ($fichier instanceof UploadedFile ? $fichier->getClientOriginalName() : basename((string) $fichier));

        $extrait = $this->texteExtrait->depuis($fichier);
        $texte = mb_substr($extrait['texte'], 0, 5000);

        if (mb_strlen(trim($texte)) < 20) {
            throw new RuntimeException(
                'Aucun texte lisible. Utilisez un fichier .txt ou .docx.'
            );
        }

        // 1) OpenRouter si clé + réseau OK
        if ($this->client->configure()) {
            try {
                return $this->viaOpenRouter($texte, $nom);
            } catch (Throwable) {
                // Timeout / DNS / 429 → lecture locale
            }
        }

        // 2) Toujours une réponse utilisable
        return $this->normaliser($this->local->analyser($texte, $nom));
    }

    /**
     * @return array{type: ?string, resultat: ?string, date_prelevement: ?string, date_resultat: ?string, commentaire: ?string, confiance: ?float, resume_court: ?string, titre_resume: ?string, resume_qualite: ?string}
     */
    private function viaOpenRouter(string $texte, string $nom): array
    {
        $typesListe = implode(', ', array_keys(config('nutritrace.options.analyse_types')));
        $resultatsListe = implode(', ', array_keys(config('nutritrace.options.analyse_resultats')));

        $reponse = $this->client->chat([
            [
                'role' => 'system',
                'content' => 'Tu es NutriTrace. Réponds UNIQUEMENT avec un objet JSON valide, sans markdown.',
            ],
            [
                'role' => 'user',
                'content' => <<<PROMPT
Lis le rapport « {$nom} » et renvoie ce JSON :
{"type":"parmi: {$typesListe}","resultat":"parmi: {$resultatsListe}","date_prelevement":"YYYY-MM-DD ou null","date_resultat":"YYYY-MM-DD ou null","commentaire":"max 200","confiance":0.8,"titre_resume":"titre court","resume_qualite":"2-3 phrases avec **mots**"}

=== RAPPORT ===
{$texte}
PROMPT,
            ],
        ], 0.1, null, true);

        try {
            $data = $this->client->decoderJson($reponse['content']);
        } catch (RuntimeException) {
            $data = $this->local->analyser($texte, $nom);
            if (trim($reponse['content']) !== '') {
                $data['resume_qualite'] = mb_substr(trim($reponse['content']), 0, 500);
            }
        }

        return $this->normaliser($data);
    }

    public function resumer(Analyse $analyse): string
    {
        $analyse->loadMissing(['lot.produit', 'laboratoire']);
        $contexte = "{$analyse->numero} · {$analyse->typeLabel()} · {$analyse->resultatLabel()} · "
            .($analyse->commentaire ?: 'sans commentaire');

        $texteDoc = '';
        if ($analyse->rapport) {
            try {
                $texteDoc = mb_substr($this->texteExtrait->depuis($analyse->rapport)['texte'], 0, 3000);
            } catch (RuntimeException) {
            }
        }

        if ($this->client->configure()) {
            try {
                $resume = mb_substr(trim($this->client->chat([
                    ['role' => 'user', 'content' => "Résume en 3 phrases :\n{$contexte}\n{$texteDoc}"],
                ], 0.3)['content']), 0, 800);

                $analyse->forceFill(['resume_ia' => $resume, 'ia_genere_at' => now()])->save();

                return $resume;
            } catch (Throwable) {
            }
        }

        $local = $this->local->analyser($texteDoc !== '' ? $texteDoc : $contexte, $analyse->numero ?? 'rapport');
        $resume = mb_substr(strip_tags(str_replace('**', '', $local['resume_qualite'])), 0, 800);
        $analyse->forceFill(['resume_ia' => $resume, 'ia_genere_at' => now()])->save();

        return $resume;
    }

    /**
     * @return array{niveau: string, score: int, libelle: string, justification: string, alertes: list<string>}
     */
    public function evaluerRisque(Lot $lot): array
    {
        $lot->loadMissing(['analyses', 'produit:id,nom']);
        $analyses = $lot->analyses;
        $alertes = [];
        $score = $analyses->isEmpty() ? 55 : 15;
        $seuil = (int) config('openrouter.alerte_en_attente_jours', 7);

        if ($analyses->isEmpty()) {
            $alertes[] = 'Aucune analyse.';
        }

        $nonConformes = $analyses->where('resultat', 'non_conforme')->count();
        if ($nonConformes > 0) {
            $score += 45 + min(20, ($nonConformes - 1) * 10);
            $alertes[] = "{$nonConformes} non conforme(s).";
        }

        foreach ($analyses->where('resultat', 'en_attente') as $a) {
            $jours = $a->date_prelevement?->diffInDays(today()) ?? 0;
            $score += $jours >= $seuil ? 12 : 5;
            if ($jours >= $seuil) {
                $alertes[] = "{$a->numero} en attente {$jours} j.";
            }
        }

        $conformes = $analyses->where('resultat', 'conforme')->count();
        if ($conformes > 0 && $nonConformes === 0) {
            $score = max(5, $score - min(25, $conformes * 8));
        }

        $score = max(0, min(100, $score));
        $niveau = $score >= 70 ? 'eleve' : ($score >= 40 ? 'moyen' : 'faible');
        $libelle = ['eleve' => 'Risque élevé', 'moyen' => 'Risque moyen', 'faible' => 'Risque faible'][$niveau];
        $justification = $libelle.' ('.$score.'/100).';

        if ($this->client->configure()) {
            try {
                $justification = mb_substr(trim($this->client->chat([
                    ['role' => 'user', 'content' => "Lot {$lot->numero_lot} : {$libelle} {$score}/100. 2 phrases."],
                ], 0.3)['content']), 0, 500);
            } catch (Throwable) {
            }
        }

        return compact('niveau', 'score', 'libelle', 'justification', 'alertes');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: ?string, resultat: ?string, date_prelevement: ?string, date_resultat: ?string, commentaire: ?string, confiance: ?float, resume_court: ?string, titre_resume: ?string, resume_qualite: ?string}
     */
    private function normaliser(array $data): array
    {
        $types = array_keys(config('nutritrace.options.analyse_types'));
        $resultats = array_keys(config('nutritrace.options.analyse_resultats'));
        $resume = isset($data['resume_qualite']) ? mb_substr(trim((string) $data['resume_qualite']), 0, 600) : null;
        $court = isset($data['resume_court']) ? mb_substr(trim((string) $data['resume_court']), 0, 200) : null;

        return [
            'type' => in_array($data['type'] ?? null, $types, true) ? $data['type'] : null,
            'resultat' => in_array($data['resultat'] ?? null, $resultats, true) ? $data['resultat'] : null,
            'date_prelevement' => $this->dateOuNull($data['date_prelevement'] ?? null),
            'date_resultat' => $this->dateOuNull($data['date_resultat'] ?? null),
            'commentaire' => isset($data['commentaire']) ? mb_substr(trim((string) $data['commentaire']), 0, 400) : null,
            'confiance' => isset($data['confiance']) ? round((float) $data['confiance'], 2) : null,
            'resume_court' => $court,
            'titre_resume' => isset($data['titre_resume']) ? mb_substr(trim((string) $data['titre_resume']), 0, 60) : 'Synthèse qualité',
            'resume_qualite' => $resume ?: $court,
        ];
    }

    private function dateOuNull(mixed $valeur): ?string
    {
        return is_string($valeur) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valeur) ? $valeur : null;
    }
}
