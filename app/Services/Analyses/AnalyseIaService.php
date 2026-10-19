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

        $format = "Réponds en français, ton professionnel, avec EXACTEMENT ces 4 lignes (une par section, détaillées) :\n"
            ."Verdict : <décision claire et motivée>\n"
            ."Points confirmés : <ce qui est conforme / prouvé, avec chiffres si présents>\n"
            ."Points de vigilance : <risques, attentes, limites — ou « aucun point bloquant »>\n"
            ."Recommandation : <action concrète pour le consommateur ou le laboratoire>\n";

        if ($this->client->configure()) {
            try {
                $resume = mb_substr(trim($this->client->chat([
                    ['role' => 'user', 'content' => "Rédige une synthèse qualité structurée. {$format}{$contexte}\n{$texteDoc}"],
                ], 0.3)['content']), 0, 1200);

                if (! str_contains($resume, 'Verdict')) {
                    $resume = $this->resumeStructureLocal($analyse);
                }

                $analyse->forceFill(['resume_ia' => $resume, 'ia_genere_at' => now()])->save();

                return $resume;
            } catch (Throwable) {
            }
        }

        $resume = $this->resumeStructureLocal($analyse);
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

        // ── Score qualité seul (analyses laboratoire) ──────────────────────
        $scoreQualite = max(0, min(100, $score));

        // ── Moteur profil : le score CHANGE selon l'état de santé déclaré ──
        $moteur = $this->analyserProfils($profilSante, $lot);
        $penalite = $moteur['penalite'];

        $score = max(0, min(100, $scoreQualite + $penalite));
        $niveau = $score >= 70 ? 'eleve' : ($score >= 40 ? 'moyen' : 'faible');
        $libelle = ['eleve' => 'Risque élevé', 'moyen' => 'Risque moyen', 'faible' => 'Risque faible'][$niveau];

        $aptitude = null;
        $aptitudeLabel = null;
        if ($profilSante) {
            $aptitude = match (true) {
                $scoreQualite >= 70, $penalite >= 25 => 'deconseille',
                $penalite >= 10, $niveau === 'moyen' => 'prudence',
                default => 'adapte',
            };
            $aptitudeLabel = [
                'adapte' => 'Plutôt adapté à votre profil',
                'prudence' => 'À consommer avec prudence',
                'deconseille' => 'Peu recommandé pour votre profil',
            ][$aptitude];
        }

        // Justification enrichie par IA uniquement sans profil (le moteur
        // profil, lui, est déterministe pour garantir des scores différents).
        $justification = null;
        if (! $profilSante && $this->client->configure()) {
            try {
                $ia = $this->risqueViaOpenRouter($lot, $score, $niveau, $libelle, $alertes, $profilSante);
                if (! empty($ia['justification']) && ! in_array(strtolower((string) $ia['justification']), ['null', 'none'], true)) {
                    $justification = $this->nettoyerTexte(mb_substr(trim((string) $ia['justification']), 0, 700));
                }
            } catch (Throwable) {
                // conserve le calcul déterministe
            }
        }
        $justification ??= $this->justificationDetaillee($lot, $libelle, $score, $scoreQualite, $moteur, $alertes);

        $conseils = $moteur['conseils'];
        $conseilSante = $conseils !== [] ? implode(' ', $conseils) : null;

        return [
            'niveau' => $niveau,
            'score' => $score,
            'score_qualite' => $scoreQualite,
            'score_profil' => $penalite,
            'libelle' => $libelle,
            'justification' => $justification,
            'alertes' => $alertes,
            'conseil_sante' => $conseilSante,
            'conseils' => $conseils,
            'profils_detectes' => $moteur['profils'],
            'facteurs' => $moteur['facteurs'],
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
            ? "Profil déclaré par le consommateur : « {$profilSante} ».\nDonne aptitude (adapte|prudence|deconseille) et un conseil_sante détaillé et actionnable."
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
{"score":{$scoreBase},"justification":"4-5 phrases détaillées et concrètes sur le risque qualité","conseil_sante":"null ou texte détaillé","aptitude":"null|adapte|prudence|deconseille"}
Sois précis, factuel et orienté action.

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
     * Synthèse structurée en 4 sections colorées côté affichage.
     */
    private function resumeStructureLocal(Analyse $analyse): string
    {
        $analyse->loadMissing(['lot.produit', 'laboratoire']);
        $produit = $analyse->lot?->produit?->nom ?? 'ce produit';
        $labo = $analyse->laboratoire?->nom ?? 'le laboratoire';
        $type = $analyse->typeLabel();
        $resultat = $analyse->resultatLabel();

        $verdict = match ($analyse->resultat) {
            'conforme' => "L'analyse {$type} du lot de « {$produit} » est CONFORME : le contrôle réalisé par {$labo} valide ce point de qualité.",
            'non_conforme' => "L'analyse {$type} du lot de « {$produit} » est NON CONFORME : le résultat de {$labo} impose un blocage du lot jusqu'à traitement du problème.",
            default => "L'analyse {$type} du lot de « {$produit} » est EN ATTENTE de résultat chez {$labo} : la décision qualité n'est pas encore arrêtée.",
        };

        $confirmes = "Type d'analyse : {$type}. Prélèvement du ".($analyse->date_prelevement?->format('d/m/Y') ?? '—')
            .($analyse->date_resultat ? ', résultat rendu le '.$analyse->date_resultat->format('d/m/Y') : '')
            .". Laboratoire : {$labo}. Référence : {$analyse->numero}."
            .($analyse->commentaire ? " Observation du laboratoire : {$analyse->commentaire}" : '');

        $vigilance = match ($analyse->resultat) {
            'conforme' => "Aucun point bloquant sur cette analyse. La vigilance reste de routine : conservation, date limite et analyses complémentaires du lot.",
            'non_conforme' => "Non-conformité à traiter en priorité : identifier la cause (matière première, process, conservation), isoler le lot et prévoir une contre-analyse avant toute remise en circulation.",
            default => $analyse->estEnRetard()
                ? "Résultat en retard : relancer {$labo} et ne pas considérer le lot comme validé tant que le résultat n'est pas connu."
                : "Résultat attendu : le lot reste en validation jusqu'à réception du rapport de {$labo}.",
        };

        $recommandation = match ($analyse->resultat) {
            'conforme' => "Produit consommable dans les conditions normales d'usage. Conservez le rapport {$analyse->numero} comme preuve qualité du lot.",
            'non_conforme' => "Ne pas consommer / ne pas commercialiser ce lot en l'état : suivre la procédure qualité (retrait, contre-expertise, décision tracée).",
            default => "Attendre le résultat avant de statuer ; si le délai dépasse le seuil habituel, contacter directement {$labo}.",
        };

        return "Verdict : {$verdict}\nPoints confirmés : {$confirmes}\nPoints de vigilance : {$vigilance}\nRecommandation : {$recommandation}";
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

    /**
     * Moteur profil intelligent (déterministe) : détecte les états de santé
     * déclarés, recherche les facteurs nutritionnels du produit et calcule
     * une pénalité de score propre à chaque profil.
     *
     * @return array{
     *     profils: list<array{key: string, label: string}>,
     *     facteurs: list<array{profil: string, libelle: string, impact: int, detail: string}>,
     *     conseils: list<string>,
     *     penalite: int
     * }
     */
    private function analyserProfils(?string $profilSante, Lot $lot): array
    {
        if (! $profilSante) {
            return ['profils' => [], 'facteurs' => [], 'conseils' => [], 'penalite' => 0];
        }

        $p = mb_strtolower($profilSante);
        $produit = $lot->produit;
        $nom = $produit?->nom ?? 'ce produit';
        $texte = mb_strtolower(trim(
            ($produit?->composition ?? '').' '.($produit?->description ?? '').' '.($produit?->nom ?? '').' '.($produit?->categorie?->nom ?? '')
        ));

        // ── Base de connaissances produits (recherche avant verdict) ─────
        // On déduit la nature réelle du produit depuis son nom, sa catégorie
        // et sa composition : un soda reste sucré même si « sucre » n'est pas
        // écrit dans la composition (ex. Boga, citronnade, nectar…).
        $nomMin = mb_strtolower($nom);
        $categorieMin = mb_strtolower($produit?->categorie?->nom ?? '');
        $estEau = preg_match('/eau min[eé]rale|eau de source|eau gazeuse|eau plate/u', $nomMin) === 1;
        $estSoda = ! $estEau && preg_match('/boga|coca|pepsi|fanta|sprite|orangina|cola|soda|cidre|boisson gazeuse|gazeuse/u', $texte) === 1;
        $estCitronnade = preg_match('/citronnade|limonade/u', $texte) === 1;
        $estJus = preg_match('/\bjus\b|nectar|smoothie/u', $texte) === 1;
        $estBoissonEnergisante = preg_match('/boisson [eé]nergisante|energy drink/u', $texte) === 1;
        $estBoissonSucree = $estSoda || $estCitronnade || $estJus || $estBoissonEnergisante
            || (! $estEau && str_contains($categorieMin, 'boisson') && preg_match('/sucre|sirop|ar[oô]me/u', $texte) === 1);
        $estGlucideRaffine = preg_match('/couscous|semoule|p[aâ]tes|pain blanc|riz blanc|farine blanche/u', $texte) === 1;

        $profils = [];
        $facteurs = [];
        $conseils = [];
        $penalite = 0;

        $ajouter = function (string $profil, string $libelle, int $impact, string $detail) use (&$facteurs, &$penalite) {
            $facteurs[] = ['profil' => $profil, 'libelle' => $libelle, 'impact' => $impact, 'detail' => $detail];
            $penalite += $impact;
        };
        $trouve = fn (string $regex) => preg_match($regex, $texte) === 1;

        // ── Diabète / sucre ───────────────────────────────────────────────
        if (preg_match('/diab|glyc|sucre/u', $p)) {
            $profils[] = ['key' => 'diabete', 'label' => 'Diabète / sucre'];
            $risques = 0;
            foreach ([
                ['/sucre ajout|sucre\b/u', 'Sucres présents', 16, 'Des sucres sont déclarés dans la composition : ils font monter la charge glycémique.'],
                ['/miel/u', 'Miel', 14, 'Le miel est un sucre rapide : même naturel, il compte comme glucide simple.'],
                ['/datte/u', 'Dattes', 12, 'Les dattes sont concentrées en sucres naturels (fructose/glucose).'],
                ['/sirop|confiture|chocolat|bonbon|p[aâ]tisserie|g[aâ]teau/u', 'Produit sucré', 14, 'Profil de produit sucré / dessert : portions à maîtriser.'],
            ] as [$regex, $libelle, $impact, $detail]) {
                if ($trouve($regex)) {
                    $ajouter('Diabète / sucre', $libelle, $impact, $detail);
                    $risques++;
                }
            }
            // Connaissances boissons : un soda / une citronnade est chargé en
            // sucres rapides même sans mention « sucre » dans la composition.
            if ($estSoda || $estBoissonEnergisante) {
                $ajouter('Diabète / sucre', 'Boisson gazeuse sucrée (soda)', 26, "« {$nom} » est un soda : environ 10 g de sucres rapides par 100 ml, soit près de 26 g pour un verre de 25 cl — sans fibres pour freiner l'absorption. Chez une personne diabétique, cela provoque un pic glycémique rapide et marqué.");
                $risques++;
            }
            if ($estCitronnade) {
                $ajouter('Diabète / sucre', 'Citronnade / limonade sucrée', 26, "La citronnade est une boisson sucrée (sucre ajouté systématique, maison ou industriel) : son acidité masque le sucre, mais un verre apporte autant de sucres rapides qu'un soda.");
                $risques++;
            }
            if ($estJus && ! $estSoda) {
                $ajouter('Diabète / sucre', 'Jus / nectar de fruits', 16, 'Un jus ou nectar concentre les sucres de plusieurs fruits sans leurs fibres : la glycémie monte presque aussi vite qu’avec un soda, même « 100 % fruits ».');
                $risques++;
            }
            if ($estGlucideRaffine) {
                $ajouter('Diabète / sucre', 'Glucides raffinés à index glycémique élevé', 12, "Semoule, couscous, pâtes ou riz blancs se transforment vite en glucose : l'index glycémique est élevé, surtout en grande portion et sans légumes.");
                $risques++;
            }
            if ($estEau) {
                $ajouter('Diabète / sucre', 'Eau : boisson de référence', -8, "L'eau minérale n'apporte ni sucre ni calories : c'est la boisson la plus sûre au quotidien pour la glycémie.");
            }
            if ($trouve('/sans sucre|0\s?% sucre|riche en fibres|complet/u')) {
                $ajouter('Diabète / sucre', 'Atouts fibres / sans sucre', -8, 'Des éléments favorables (fibres ou absence de sucre ajouté) atténuent la charge glycémique.');
            }
            $conseils[] = $estBoissonSucree
                ? "Diabète / sucre : « {$nom} » fait partie des boissons sucrées — ce n'est pas un choix adapté au quotidien avec un diabète. Privilégiez l'eau, l'eau citronnée sans sucre ou une version « zéro sucre ». Si vous en consommez exceptionnellement : un petit verre (12 cl maximum), pendant un repas (jamais à jeun ni pour étancher la soif), et pas plus d'une fois par semaine."
                : ($risques > 0
                    ? "Diabète / sucre : « {$nom} » contient des facteurs sucrés détaillés ci-dessus. Servez-vous une portion mesurée (environ la moitié d'une portion habituelle), consommez-le au cours d'un repas riche en fibres et en protéines plutôt qu'à jeun, et évitez d'y ajouter sucre, miel ou jus en accompagnement."
                    : "Diabète / sucre : aucun facteur sucré majeur détecté dans « {$nom} ». Il s'intègre dans une alimentation équilibrée en gardant des portions régulières et en l'associant à des fibres (légumes, céréales complètes).");
        }

        // ── Hypertension / sel ────────────────────────────────────────────
        if (preg_match('/hypertension|tension|sel|sodium/u', $p)) {
            $profils[] = ['key' => 'hypertension', 'label' => 'Hypertension / sel'];
            $risques = 0;
            foreach ([
                ['/sel|sodium/u', 'Sel / sodium', 18, 'Le sel (sodium) favorise la rétention d\u2019eau et la hausse de la tension artérielle.'],
                ['/saumure|fum[eé]|marin[eé]/u', 'Préparation salée', 12, 'Saumure ou fumage = apports sodés concentrés.'],
                ['/charcuterie|saucisson|jambon/u', 'Charcuterie', 14, 'Les charcuteries cumulent sel et graisses saturées.'],
                ['/conserve|fromage/u', 'Sodium caché', 8, 'Conserves et fromages apportent souvent un sodium peu visible.'],
            ] as [$regex, $libelle, $impact, $detail]) {
                if ($trouve($regex)) {
                    $ajouter('Hypertension / sel', $libelle, $impact, $detail);
                    $risques++;
                }
            }
            if ($trouve('/sans sel|faible en sodium|non sal/u')) {
                $ajouter('Hypertension / sel', 'Pauvre en sel', -10, 'La mention pauvre en sel est un vrai atout pour la tension.');
            }
            $conseils[] = $risques > 0
                ? "Hypertension / sel : « {$nom} » présente des facteurs sodés. Limitez la portion, ne rajoutez pas de sel à table, accompagnez de légumes frais ou d'une salade non assaisonnée, et buvez de l'eau tout au long du repas pour équilibrer l'apport sodé de la journée."
                : "Hypertension / sel : pas de facteur sodé marqué repéré dans « {$nom} ». Restez sur cette voie : cuisinez sans rajouter de sel et privilégiez herbes, citron et épices pour le goût.";
        }

        // ── Cholestérol / graisses ────────────────────────────────────────
        if (preg_match('/cholest|graisse|lipid/u', $p)) {
            $profils[] = ['key' => 'cholesterol', 'label' => 'Cholestérol / graisses'];
            $risques = 0;
            foreach ([
                ['/beurre|cr[eè]me|fromage/u', 'Graisses saturées', 12, 'Beurre, crème et fromages élèvent le LDL (mauvais cholestérol).'],
                ['/huile de palme|gras|frit/u', 'Graisses cachées', 12, 'Graisses de cuisson ou de palme : impact direct sur le bilan lipidique.'],
                ['/charcuterie|viande rouge/u', 'Viandes grasses', 10, 'Ces produits cumulent graisses saturées et sel.'],
            ] as [$regex, $libelle, $impact, $detail]) {
                if ($trouve($regex)) {
                    $ajouter('Cholestérol / graisses', $libelle, $impact, $detail);
                    $risques++;
                }
            }
            $conseils[] = $risques > 0
                ? "Cholestérol : « {$nom} » contient des graisses à surveiller. Réduisez la fréquence (plutôt occasionnel), choisissez une petite portion et équilibrez l'assiette avec légumes, légumineuses et une huile végétale de qualité (olive, colza)."
                : "Cholestérol : aucun facteur gras majeur détecté dans « {$nom} » : il reste compatible avec une alimentation protectrice du cœur, variée et riche en fibres.";
        }

        // ── Allergies / intolérances ──────────────────────────────────────
        if (preg_match('/allerg|intol[eé]ran|lactose|gluten|arachide|noix|soja|oeuf|œuf/u', $p)) {
            $profils[] = ['key' => 'allergie', 'label' => 'Allergie / intolérance'];
            $allergenes = [
                ['lactose', '/lait|lactose|yaourt|fromage|beurre|cr[eè]me/u', 'Lait / lactose'],
                ['gluten', '/bl[eé]|farine|p[aâ]tes|pain|biscuit|orge|seigle/u', 'Gluten (blé, farines)'],
                ['arachide', '/arachide|cacahu[eè]te/u', 'Arachide'],
                ['noix', '/noix|noisette|amande|pistache|cajou/u', 'Fruits à coque'],
                ['soja', '/soja/u', 'Soja'],
                ['oeuf', '/oeuf|œuf/u', 'Œuf'],
                ['poisson', '/poisson|thon|saumon|sardine/u', 'Poisson'],
                ['s[eé]same', '/s[eé]same/u', 'Sésame'],
            ];
            $trouves = [];
            foreach ($allergenes as [$mot, $regex, $libelle]) {
                if ($trouve($regex)) {
                    $trouves[] = $libelle;
                    if (str_contains($p, $mot)) {
                        $ajouter('Allergie / intolérance', $libelle.' détecté', 22, "Votre profil mentionne « {$mot} » et la composition en contient : risque réel de réaction.");
                    }
                }
            }
            if ($trouves !== [] && $facteurs === []) {
                $ajouter('Allergie / intolérance', 'Allergènes présents', 8, 'Allergènes repérés dans la composition : '.implode(', ', $trouves).'. À vérifier selon votre allergie précise.');
            }
            $conseils[] = $trouves !== []
                ? 'Allergie / intolérance : allergènes repérés ('.implode(', ', $trouves)."). Lisez la liste complète des ingrédients avant de consommer « {$nom} », vérifiez les mentions « peut contenir des traces de » et, en cas de doute sur un ingrédient, choisissez une alternative sans l'allergène concerné."
                : "Allergie / intolérance : aucun des allergènes majeurs surveillés n'a été repéré dans la composition déclarée de « {$nom} ». Gardez le réflexe de relire l'étiquette à chaque nouvel achat, les recettes pouvant évoluer.";
        }

        // ── Grossesse ─────────────────────────────────────────────────────
        if (preg_match('/enceinte|grossesse/u', $p)) {
            $profils[] = ['key' => 'grossesse', 'label' => 'Grossesse'];
            if ($trouve('/lait cru|non pasteuris/u')) {
                $ajouter('Grossesse', 'Lait cru / non pasteurisé', 22, 'Le lait cru expose au risque de listériose pendant la grossesse.');
            }
            if ($trouve('/alcool|vin|bi[eè]re/u')) {
                $ajouter('Grossesse', 'Alcool', 25, 'Aucune quantité d\u2019alcool n\u2019est considérée sans risque pendant la grossesse.');
            }
            if ($trouve('/charcuterie|poisson fum/u')) {
                $ajouter('Grossesse', 'Produits à risque listériose', 10, 'Charcuteries et poissons fumés demandent une vigilance renforcée.');
            }
            $conseils[] = "Grossesse : privilégiez les produits pasteurisés et bien cuits, lavez soigneusement les végétaux consommés crus et respectez strictement les dates limites de consommation du lot.";
        }

        // ── Enfant ────────────────────────────────────────────────────────
        if (preg_match('/enfant|b[eé]b[eé]|nourrisson/u', $p)) {
            $profils[] = ['key' => 'enfant', 'label' => 'Enfant'];
            if ($estBoissonSucree) {
                $ajouter('Enfant', 'Boisson sucrée', 14, 'Sodas, citronnades et jus sucrés entretiennent le goût du sucre et les caries chez l’enfant : l’eau reste la boisson du quotidien.');
            }
            if ($trouve('/sucre|miel|chocolat|bonbon/u')) {
                $ajouter('Enfant', 'Sucres', 10, 'Chez l\u2019enfant, les sucres fréquents favorisent caries et habitudes sucrées.');
            }
            if ($trouve('/sel|charcuterie|chips/u')) {
                $ajouter('Enfant', 'Sel', 8, 'Les reins des jeunes enfants tolèrent mal les excès de sel.');
            }
            $conseils[] = "Enfant : proposez « {$nom} » en portion adaptée à l'âge, plutôt au goûter accompagné d'un fruit ou d'un laitage nature, et limitez la fréquence des produits sucrés ou salés.";
        }

        if ($profils === []) {
            $profils[] = ['key' => 'personnalise', 'label' => 'Profil personnalisé'];
            $conseils[] = "Profil « {$profilSante} » bien pris en compte pour « {$nom} » : le verdict combine la qualité laboratoire du lot (analyses ci-dessus) et la composition du produit. Plus votre description est précise (ex. « diabète de type 2 », « intolérance au lactose »), plus les facteurs détectés et les conseils seront ciblés.";
        }

        return [
            'profils' => $profils,
            'facteurs' => $facteurs,
            'conseils' => $conseils,
            'penalite' => max(-12, min(45, $penalite)),
        ];
    }

    /**
     * Justification détaillée et structurée du score (qualité + profil).
     *
     * @param  array{profils: list<array{key: string, label: string}>, facteurs: list<array{profil: string, libelle: string, impact: int, detail: string}>, conseils: list<string>, penalite: int}  $moteur
     * @param  list<string>  $alertes
     */
    private function justificationDetaillee(Lot $lot, string $libelle, int $score, int $scoreQualite, array $moteur, array $alertes): string
    {
        $produit = $lot->produit?->nom ?? 'ce produit';
        $nb = $lot->analyses->count();
        $texte = "{$libelle} ({$score}/100) pour « {$produit} » (lot {$lot->numero_lot}). ";
        $texte .= $nb > 0
            ? "Côté laboratoire, {$nb} analyse(s) donnent un score qualité de {$scoreQualite}/100. "
            : 'Aucune analyse laboratoire pour ce lot : le score repose sur la composition et le profil. ';

        if ($alertes !== []) {
            $texte .= 'Vigilance laboratoire : '.implode(' ', $alertes).' ';
        }

        if ($moteur['profils'] !== []) {
            $labels = implode(', ', array_column($moteur['profils'], 'label'));
            $sens = $moteur['penalite'] > 0 ? "+{$moteur['penalite']} points de risque" : ($moteur['penalite'] < 0 ? "{$moteur['penalite']} points (facteurs favorables)" : 'aucun facteur aggravant détecté');
            $texte .= "Votre profil ({$labels}) ajoute {$sens} après lecture de la composition. ";

            foreach ($moteur['facteurs'] as $f) {
                $texte .= "{$f['libelle']} ({$f['profil']}) : {$f['detail']} ";
            }
        }

        return trim($texte);
    }

    private function nettoyerTexte(string $texte): string
    {
        // Retire toute phrase de type « pas un avis médical » des textes IA.
        $phrases = preg_split('/(?<=[.!?])\s+/', $texte) ?: [];

        return trim(implode(' ', array_filter(
            $phrases,
            fn (string $phrase) => ! preg_match('/avis m[eé]dical|pas un m[eé]decin|diagnostic/u', mb_strtolower($phrase))
        )));
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
