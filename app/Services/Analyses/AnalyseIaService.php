<?php

namespace App\Services\Analyses;

use App\Models\Analyse;
use App\Models\Lot;
use App\Services\OpenRouter\OpenRouterClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Features IA du module 2 : lecture PDF, résumé, score de risque lot.
 */
class AnalyseIaService
{
    public function __construct(private OpenRouterClient $client) {}

    public function disponible(): bool
    {
        return $this->client->configure();
    }

    /**
     * Extrait type / résultat / dates / commentaire depuis un PDF de rapport.
     *
     * @return array{type: ?string, resultat: ?string, date_prelevement: ?string, date_resultat: ?string, commentaire: ?string, confiance: ?float, resume_court: ?string}
     */
    public function extraireDepuisPdf(UploadedFile|string $pdf, ?string $nomFichier = null): array
    {
        [$base64, $filename] = $this->encoderPdf($pdf, $nomFichier);
        $types = implode(', ', array_keys(config('nutritrace.options.analyse_types')));
        $resultats = implode(', ', array_keys(config('nutritrace.options.analyse_resultats')));

        $reponse = $this->client->chat([
            [
                'role' => 'system',
                'content' => 'Tu es un assistant NutriTrace spécialisé dans les rapports d\'analyses alimentaires. '
                    .'Tu réponds UNIQUEMENT en JSON valide, sans markdown.',
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => <<<PROMPT
Lis ce rapport PDF de laboratoire et extrais les champs pour NutriTrace.

Réponds uniquement avec ce JSON :
{
  "type": "une des valeurs : {$types}",
  "resultat": "une des valeurs : {$resultats}",
  "date_prelevement": "YYYY-MM-DD ou null",
  "date_resultat": "YYYY-MM-DD ou null",
  "commentaire": "résumé factuel du laboratoire en français (max 400 caractères)",
  "confiance": 0.0,
  "resume_court": "1 phrase claire pour un non-spécialiste"
}

Règles :
- Si le résultat n'est pas clair, mets "en_attente".
- Si une date est absente, mets null.
- Ne invente pas de valeurs non présentes dans le document.
PROMPT,
                    ],
                    [
                        'type' => 'file',
                        'file' => [
                            'filename' => $filename,
                            'file_data' => 'data:application/pdf;base64,'.$base64,
                        ],
                    ],
                ],
            ],
        ]);

        $data = $this->client->decoderJson($reponse['content']);
        $typesValides = array_keys(config('nutritrace.options.analyse_types'));
        $resultatsValides = array_keys(config('nutritrace.options.analyse_resultats'));

        return [
            'type' => in_array($data['type'] ?? null, $typesValides, true) ? $data['type'] : null,
            'resultat' => in_array($data['resultat'] ?? null, $resultatsValides, true) ? $data['resultat'] : null,
            'date_prelevement' => $this->dateOuNull($data['date_prelevement'] ?? null),
            'date_resultat' => $this->dateOuNull($data['date_resultat'] ?? null),
            'commentaire' => isset($data['commentaire']) ? mb_substr(trim((string) $data['commentaire']), 0, 1000) : null,
            'confiance' => isset($data['confiance']) ? round((float) $data['confiance'], 2) : null,
            'resume_court' => isset($data['resume_court']) ? mb_substr(trim((string) $data['resume_court']), 0, 300) : null,
        ];
    }

    /**
     * Génère et enregistre un résumé IA sur l'analyse.
     */
    public function resumer(Analyse $analyse): string
    {
        $contexte = $this->contexteAnalyse($analyse);
        $contenuUser = [
            [
                'type' => 'text',
                'text' => <<<PROMPT
Rédige un résumé clair (3 à 5 phrases max) en français simple pour un professionnel agroalimentaire.
Ne invente rien. Si une info manque, dis-le.

Données de l'analyse :
{$contexte}
PROMPT,
            ],
        ];

        if ($analyse->rapport && Storage::disk(Analyse::DISQUE)->exists($analyse->rapport)) {
            $base64 = base64_encode(Storage::disk(Analyse::DISQUE)->get($analyse->rapport));
            $contenuUser[] = [
                'type' => 'file',
                'file' => [
                    'filename' => basename($analyse->rapport),
                    'file_data' => 'data:application/pdf;base64,'.$base64,
                ],
            ];
        }

        $reponse = $this->client->chat([
            [
                'role' => 'system',
                'content' => 'Tu es l\'assistant qualité NutriTrace. Tu résumes des analyses labo en français clair, sans jargon inutile.',
            ],
            ['role' => 'user', 'content' => $contenuUser],
        ], 0.3);

        $resume = mb_substr(trim($reponse['content']), 0, 2000);
        $analyse->forceFill([
            'resume_ia' => $resume,
            'ia_genere_at' => now(),
        ])->save();

        return $resume;
    }

    /**
     * Score de risque qualité d'un lot à partir de ses analyses (+ justification IA).
     *
     * @return array{niveau: string, score: int, libelle: string, justification: string, alertes: list<string>}
     */
    public function evaluerRisque(Lot $lot): array
    {
        $lot->loadMissing(['analyses.laboratoire', 'produit:id,nom']);
        $analyses = $lot->analyses;

        $alertes = [];
        $score = 15;

        if ($analyses->isEmpty()) {
            $score = 55;
            $alertes[] = 'Aucune analyse enregistrée pour ce lot.';
        }

        $nonConformes = $analyses->where('resultat', 'non_conforme')->count();
        $enAttente = $analyses->where('resultat', 'en_attente');
        $conformes = $analyses->where('resultat', 'conforme')->count();
        $seuilJours = (int) config('openrouter.alerte_en_attente_jours', 7);

        if ($nonConformes > 0) {
            $score += 45 + min(20, ($nonConformes - 1) * 10);
            $alertes[] = "{$nonConformes} analyse(s) non conforme(s).";
        }

        foreach ($enAttente as $analyse) {
            $jours = $analyse->date_prelevement?->diffInDays(today()) ?? 0;
            if ($jours >= $seuilJours) {
                $score += 12;
                $alertes[] = "{$analyse->numero} en attente depuis {$jours} jours.";
            } else {
                $score += 5;
            }
        }

        if ($conformes > 0 && $nonConformes === 0) {
            $score = max(5, $score - min(25, $conformes * 8));
        }

        $score = max(0, min(100, $score));
        $niveau = match (true) {
            $score >= 70 => 'eleve',
            $score >= 40 => 'moyen',
            default => 'faible',
        };

        $libelle = match ($niveau) {
            'eleve' => 'Risque élevé',
            'moyen' => 'Risque moyen',
            default => 'Risque faible',
        };

        $liste = $analyses->map(fn (Analyse $a) => sprintf(
            '- %s | %s | %s | prélevé %s | résultat %s | commentaire : %s',
            $a->numero,
            $a->typeLabel(),
            $a->laboratoire?->nom ?? 'labo inconnu',
            $a->date_prelevement?->format('d/m/Y') ?? '—',
            $a->resultatLabel(),
            $a->commentaire ?: 'aucun',
        ))->implode("\n");

        $justification = $libelle.' (score '.$score.'/100).';

        if ($this->disponible()) {
            try {
                $produit = $lot->produit?->nom ?? 'produit inconnu';
                $alertesTexte = $this->joindre($alertes);
                $prompt = "Lot {$lot->numero_lot} ({$produit}).\n"
                    ."Niveau de risque calculé : {$libelle} ({$score}/100).\n"
                    ."Alertes : {$alertesTexte}.\n\n"
                    ."Analyses :\n{$liste}\n\n"
                    .'Explique brièvement pourquoi ce niveau de risque, et une action recommandée.';

                $reponse = $this->client->chat([
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert qualité NutriTrace. Réponds en 2 à 4 phrases, français simple, sans markdown.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ], 0.3);

                $justification = mb_substr(trim($reponse['content']), 0, 800);
            } catch (RuntimeException) {
                // On garde la justification déterministe si l'API échoue.
            }
        }

        return [
            'niveau' => $niveau,
            'score' => $score,
            'libelle' => $libelle,
            'justification' => $justification,
            'alertes' => $alertes,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function encoderPdf(UploadedFile|string $pdf, ?string $nomFichier): array
    {
        if ($pdf instanceof UploadedFile) {
            if ($pdf->getSize() > 5 * 1024 * 1024) {
                throw new RuntimeException('Le PDF dépasse 5 Mo.');
            }

            return [base64_encode($pdf->get()), $pdf->getClientOriginalName() ?: 'rapport.pdf'];
        }

        if (! Storage::disk(Analyse::DISQUE)->exists($pdf)) {
            throw new RuntimeException('Le rapport PDF est introuvable sur le serveur.');
        }

        return [base64_encode(Storage::disk(Analyse::DISQUE)->get($pdf)), $nomFichier ?: basename($pdf)];
    }

    private function contexteAnalyse(Analyse $analyse): string
    {
        $analyse->loadMissing(['lot.produit', 'laboratoire']);

        return collect([
            'Numéro' => $analyse->numero,
            'Lot' => $analyse->lot?->numero_lot,
            'Produit' => $analyse->lot?->produit?->nom,
            'Laboratoire' => $analyse->laboratoire?->nom,
            'Type' => $analyse->typeLabel(),
            'Résultat' => $analyse->resultatLabel(),
            'Prélèvement' => $analyse->date_prelevement?->format('d/m/Y'),
            'Date résultat' => $analyse->date_resultat?->format('d/m/Y') ?? '—',
            'Commentaire' => $analyse->commentaire ?: 'aucun',
            'Rapport PDF' => $analyse->rapport ? 'oui' : 'non',
        ])->map(fn ($v, $k) => "{$k} : {$v}")->implode("\n");
    }

    private function dateOuNull(mixed $valeur): ?string
    {
        if (! is_string($valeur) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valeur)) {
            return null;
        }

        return $valeur;
    }

    /**
     * @param  list<string>  $alertes
     */
    private function joindre(array $alertes): string
    {
        return $alertes === [] ? 'aucune' : implode(' ; ', $alertes);
    }
}
