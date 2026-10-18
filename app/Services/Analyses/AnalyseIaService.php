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
Analyse le rapport « {$nom} » et renvoie ce JSON.
Style : synthèse qualité professionnelle (laboratoire / conformité alimentaire).
Interdit : mentionner un outil, un mode local, « a lu », ou un jargon technique hors sujet.
{"type":"parmi: {$typesListe}","resultat":"parmi: {$resultatsListe}","date_prelevement":"YYYY-MM-DD ou null","date_resultat":"YYYY-MM-DD ou null","commentaire":"max 200","confiance":0.8,"titre_resume":"titre court pro","resume_qualite":"2-3 phrases pro avec **mots-clés**"}

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
                    ['role' => 'user', 'content' => "Rédige une synthèse qualité professionnelle en français (3 phrases max). "
                        ."Ton laboratoire / conformité alimentaire. Pas de jargon technique inutile, "
                        ."pas de mention d'outil, de mode local ou de lecture fichier.\n{$contexte}\n{$texteDoc}"],
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
     * @return array{
     *     niveau: string,
     *     score: int,
     *     libelle: string,
     *     justification: string,
     *     alertes: list<string>,
     *     conseil_sante: ?string,
     *     aptitude: ?string,
     *     aptitude_label: ?string,
     *     profil_sante: ?string
     * }
     */
    public function evaluerRisque(Lot $lot, ?string $profilSante = null): array
    {
        $lot->loadMissing(['analyses.laboratoire:id,nom', 'produit:id,nom,description,composition,categorie_id', 'produit.categorie:id,nom']);
        $analyses = $lot->analyses;
        $alertes = [];
        $score = $analyses->isEmpty() ? 55 : 15;
        $seuil = (int) config('openrouter.alerte_en_attente_jours', 7);
        $profilSante = $profilSante !== null ? trim(mb_substr($profilSante, 0, 240)) : null;
        if ($profilSante === '') {
            $profilSante = null;
        }

        if ($analyses->isEmpty()) {
            $alertes[] = 'Aucune analyse laboratoire disponible pour ce lot.';
        }

        $nonConformes = $analyses->where('resultat', 'non_conforme')->count();
        if ($nonConformes > 0) {
            $score += 45 + min(20, ($nonConformes - 1) * 10);
            $alertes[] = "{$nonConformes} analyse(s) non conforme(s).";
        }

        foreach ($analyses->where('resultat', 'en_attente') as $a) {
            $jours = $a->date_prelevement?->diffInDays(today()) ?? 0;
            $score += $jours >= $seuil ? 12 : 5;
            if ($jours >= $seuil) {
                $alertes[] = "{$a->numero} en attente depuis {$jours} jour(s).";
            }
        }

        $conformes = $analyses->where('resultat', 'conforme')->count();
        if ($conformes > 0 && $nonConformes === 0) {
            $score = max(5, $score - min(25, $conformes * 8));
        }

        $score = max(0, min(100, $score));
        $niveau = $score >= 70 ? 'eleve' : ($score >= 40 ? 'moyen' : 'faible');
        $libelle = ['eleve' => 'Risque élevé', 'moyen' => 'Risque moyen', 'faible' => 'Risque faible'][$niveau];
        $justification = $this->justificationRisqueLocale($lot, $libelle, $score, $alertes);
        $conseilSante = null;
        $aptitude = null;
        $aptitudeLabel = null;

        if ($this->client->configure()) {
            try {
                $ia = $this->risqueViaOpenRouter($lot, $score, $niveau, $libelle, $alertes, $profilSante);
                if (isset($ia['score']) && is_numeric($ia['score'])) {
                    $score = max(0, min(100, (int) round((float) $ia['score'])));
                    $niveau = $score >= 70 ? 'eleve' : ($score >= 40 ? 'moyen' : 'faible');
                    $libelle = ['eleve' => 'Risque élevé', 'moyen' => 'Risque moyen', 'faible' => 'Risque faible'][$niveau];
                }
                if (! empty($ia['justification']) && ! in_array(strtolower((string) $ia['justification']), ['null', 'none'], true)) {
                    $justification = mb_substr(trim((string) $ia['justification']), 0, 700);
                }
                $conseilBrut = $ia['conseil_sante'] ?? null;
                if (is_string($conseilBrut) && trim($conseilBrut) !== '' && ! in_array(strtolower(trim($conseilBrut)), ['null', 'none'], true)) {
                    $conseilSante = mb_substr(trim($conseilBrut), 0, 700);
                }
                $aptBrut = is_string($ia['aptitude'] ?? null) ? strtolower(trim((string) $ia['aptitude'])) : null;
                if ($aptBrut && in_array($aptBrut, ['adapte', 'prudence', 'deconseille'], true)) {
                    $aptitude = $aptBrut;
                    $aptitudeLabel = [
                        'adapte' => 'Plutôt adapté à votre profil',
                        'prudence' => 'À consommer avec prudence',
                        'deconseille' => 'Peu recommandé pour votre profil',
                    ][$aptitude];
                }
            } catch (Throwable) {
                // conserve le calcul déterministe
            }
        }

        if ($profilSante && $conseilSante === null) {
            $conseilSante = $this->conseilSanteLocal($lot, $profilSante, $niveau);
            $aptitude = $aptitude ?? $this->aptitudeLocale($lot, $profilSante, $niveau);
            $aptitudeLabel = [
                'adapte' => 'Plutôt adapté à votre profil',
                'prudence' => 'À consommer avec prudence',
                'deconseille' => 'Peu recommandé pour votre profil',
            ][$aptitude] ?? null;
        }

        return [
            'niveau' => $niveau,
            'score' => $score,
            'libelle' => $libelle,
            'justification' => $justification,
            'alertes' => $alertes,
            'conseil_sante' => $conseilSante,
            'aptitude' => $aptitude,
            'aptitude_label' => $aptitudeLabel,
            'profil_sante' => $profilSante,
        ];
    }

    /**
     * @param  list<string>  $alertes
     * @return array{score?: int, justification?: string, conseil_sante?: string, aptitude?: string}
     */
    private function risqueViaOpenRouter(
        Lot $lot,
        int $scoreBase,
        string $niveau,
        string $libelle,
        array $alertes,
        ?string $profilSante,
    ): array {
        $produit = $lot->produit;
        $lignesAnalyses = $lot->analyses->map(function (Analyse $a) {
            return sprintf(
                '- %s · %s · %s · labo %s · prélevé %s',
                $a->numero,
                $a->typeLabel(),
                $a->resultatLabel(),
                $a->laboratoire?->nom ?? '—',
                $a->date_prelevement?->format('Y-m-d') ?? '—'
            );
        })->implode("\n") ?: '- Aucune analyse';

        $profilBloc = $profilSante
            ? "Profil déclaré par le consommateur : « {$profilSante} ».\nDonne aptitude (adapte|prudence|deconseille) et conseil_sante utile, sans diagnostic médical."
            : "Aucun profil santé déclaré. Mets conseil_sante et aptitude à null.";

        $alertesTxt = $this->joindreAlertes($alertes);
        $nomProduit = $produit?->nom ?? '—';
        $categorie = $produit?->categorie?->nom ?? '—';
        $composition = trim(($produit?->composition ?? '').' '.($produit?->description ?? '')) ?: 'non renseignée';
        $numeroLot = $lot->numero_lot;

        $prompt = <<<PROMPT
Tu es l'analyste qualité NutriTrace (Tunisie). Évalue le risque du lot de façon professionnelle.
Score de base calculé : {$scoreBase}/100 ({$libelle}, niveau {$niveau}).
Tu peux ajuster légèrement le score (0-100) si le contexte le justifie.
Réponds UNIQUEMENT en JSON :
{"score":{$scoreBase},"justification":"3-4 phrases pro sur le risque qualité","conseil_sante":"null ou texte","aptitude":"null|adapte|prudence|deconseille"}
Disclaimer implicite : ce n'est pas un avis médical.

Produit : {$nomProduit} · catégorie {$categorie}
Composition / description : {$composition}
Lot : {$numeroLot}
Alertes : {$alertesTxt}
Analyses :
{$lignesAnalyses}

{$profilBloc}
PROMPT;

        $reponse = $this->client->chat([
            ['role' => 'system', 'content' => 'Tu réponds uniquement en JSON valide, sans markdown.'],
            ['role' => 'user', 'content' => $prompt],
        ], 0.25, null, true);

        return $this->client->decoderJson($reponse['content']);
    }

    /**
     * @param  list<string>  $alertes
     */
    private function justificationRisqueLocale(Lot $lot, string $libelle, int $score, array $alertes): string
    {
        $produit = $lot->produit?->nom ?? 'ce produit';
        $base = "{$libelle} ({$score}/100) pour le lot {$lot->numero_lot} ({$produit}). ";
        if ($alertes === []) {
            return $base.'Les indicateurs qualité disponibles ne signalent pas d\'alerte majeure.';
        }

        return $base.'Points de vigilance : '.implode(' ', $alertes);
    }

    private function conseilSanteLocal(Lot $lot, string $profil, string $niveau): string
    {
        $p = mb_strtolower($profil);
        $nom = $lot->produit?->nom ?? 'ce produit';
        $texte = $lot->produit?->composition.' '.$lot->produit?->description.' '.$nom;
        $bas = mb_strtolower($texte);

        if (preg_match('/diab|sucre|glyc[eé]/u', $p)) {
            if (preg_match('/sucre|miel|confitur|chocolat|sirop|dessert|gâteau|gateau/u', $bas)) {
                return "Compte tenu d'un profil lié au sucre / diabète, « {$nom} » semble riche en glucides simples : privilégiez une portion modérée et validez avec un professionnel de santé. Ceci n'est pas un avis médical.";
            }

            return "Pour un profil sensible au sucre, « {$nom} » peut être envisagé avec modération selon l'étiquette nutritionnelle. Demandez conseil à un professionnel de santé. Ceci n'est pas un avis médical.";
        }

        if (preg_match('/hypertension|tension|sel|sodium/u', $p)) {
            return "Pour un profil lié à la tension / sel, vérifiez la teneur en sodium de « {$nom} » et limitez les apports. Ceci n'est pas un avis médical.";
        }

        if (preg_match('/allerg|intol[eé]ran|gluten|lactose/u', $p)) {
            return "En cas d'allergie ou d'intolérance déclarée, contrôlez attentivement la composition de « {$nom} » avant consommation. Ceci n'est pas un avis médical.";
        }

        $tone = $niveau === 'eleve'
            ? 'le risque qualité du lot est élevé : la consommation n\'est pas recommandée tant que la non-conformité n\'est pas levée'
            : 'croisez toujours l\'étiquette et votre situation personnelle';

        return "Profil pris en compte (« {$profil} »). Pour « {$nom} », {$tone}. Ceci n'est pas un avis médical.";
    }

    private function aptitudeLocale(Lot $lot, string $profil, string $niveau): string
    {
        if ($niveau === 'eleve') {
            return 'deconseille';
        }

        $p = mb_strtolower($profil);
        $bas = mb_strtolower(($lot->produit?->composition ?? '').' '.($lot->produit?->nom ?? ''));

        if (preg_match('/diab|sucre|glyc[eé]/u', $p) && preg_match('/sucre|miel|confitur|chocolat|sirop|dessert/u', $bas)) {
            return 'prudence';
        }

        return $niveau === 'moyen' ? 'prudence' : 'adapte';
    }

    /**
     * @param  list<string>  $alertes
     */
    private function joindreAlertes(array $alertes): string
    {
        return $alertes === [] ? 'aucune' : implode(' | ', $alertes);
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
