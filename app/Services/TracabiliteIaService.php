<?php

namespace App\Services;

use App\Models\Lot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TracabiliteIaService
{
    private ?string $apiKey;
    private string $apiUrl;
    private string $modele;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ia.cle') ?: config('services.openrouter.key') ?: env('OPENROUTER_API_KEY');
        $this->apiUrl = rtrim((string) (config('ia.openrouter_url') ?: 'https://openrouter.ai/api/v1'), '/');
        $this->modele = (string) (config('ia.modeles.texte') ?: config('services.openrouter.model') ?: 'google/gemini-2.5-flash');
        $this->timeout = (int) (config('ia.timeout') ?: 15);
    }

    public function estConfigure(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Génère ou récupère l'histoire narrative du produit pour les consommateurs.
     */
    public function genererHistoireLot(Lot $lot, bool $force = false): ?string
    {
        // 1. Si déjà en cache et qu'on ne force pas, retourner le récit existant
        if (! $force && ! empty($lot->recit_ia)) {
            return $lot->recit_ia;
        }

        $etapes = $lot->etapes()->with('acteur')->orderBy('date_heure')->get();

        if ($etapes->isEmpty()) {
            return null;
        }

        $recit = null;

        // 2. Tenter l'appel OpenRouter si configuré
        if ($this->estConfigure()) {
            try {
                $recit = $this->appelerIaHistoire($lot, $etapes);
            } catch (Throwable $e) {
                Log::warning('Échec appel OpenRouter pour récit de lot: ' . $e->getMessage());
            }
        }

        // 3. Fallback élégant si l'IA n'est pas configurée ou en cas d'erreur
        if (empty($recit)) {
            $recit = $this->genererHistoireFallback($lot, $etapes);
        }

        // 4. Mettre en cache dans la colonne recit_ia
        $lot->updateQuietly(['recit_ia' => $recit]);

        return $recit;
    }

    /**
     * Réalise ou récupère l'audit IA de cohérence et anti-fraude du lot.
     *
     * @return array{
     *     score: int,
     *     statut: 'conforme'|'avertissement'|'anomalie',
     *     synthese: string,
     *     anomalies: array<int, string>,
     *     points_forts: array<int, string>,
     *     audite_le?: string
     * }
     */
    public function auditerParcoursLot(Lot $lot, bool $force = false): array
    {
        if (! $force && ! empty($lot->audit_ia)) {
            return $lot->audit_ia;
        }

        $etapes = $lot->etapes()->with('acteur')->orderBy('date_heure')->get();
        $resultat = null;

        if ($this->estConfigure() && $etapes->isNotEmpty()) {
            try {
                $resultat = $this->appelerIaAudit($lot, $etapes);
            } catch (Throwable $e) {
                Log::warning('Échec appel OpenRouter audit parcours: ' . $e->getMessage());
            }
        }

        if (empty($resultat) || ! isset($resultat['score'])) {
            $resultat = $this->auditerParcoursFallback($lot, $etapes);
        }

        $resultat['audite_le'] = now()->format('d/m/Y à H:i');
        $lot->updateQuietly(['audit_ia' => $resultat]);

        return $resultat;
    }

    /**
     * Génère ou récupère la recommandation IA anti-gaspillage et rotation de stock.
     *
     * @return array{
     *     urgence: 'critique'|'attention'|'optimal'|'perime'|'inconnu',
     *     statut_badge: string,
     *     titre: string,
     *     synthese: string,
     *     actions: array<int, string>,
     *     genere_le?: string
     * }
     */
    public function genererConseilAntiGaspillage(Lot $lot, bool $force = false): array
    {
        if (! $force && ! empty($lot->conseil_ia)) {
            return $lot->conseil_ia;
        }

        $resultat = null;

        if ($this->estConfigure()) {
            try {
                $resultat = $this->appelerIaAntiGaspillage($lot);
            } catch (Throwable $e) {
                Log::warning('Échec appel OpenRouter conseil anti-gaspillage: ' . $e->getMessage());
            }
        }

        if (empty($resultat) || ! isset($resultat['urgence'])) {
            $resultat = $this->genererConseilAntiGaspillageFallback($lot);
        }

        $resultat['genere_le'] = now()->format('d/m/Y à H:i');
        $lot->updateQuietly(['conseil_ia' => $resultat]);

        return $resultat;
    }

    /**
     * Analyse un texte en langage naturel pour préremplir un formulaire d'étape.
     *
     * @return array{
     *     type_etape: string,
     *     lieu: string,
     *     date_heure: string,
     *     mode_transport: string,
     *     remarques: string
     * }
     */
    public function analyserTexteEtape(string $texte): array
    {
        $texte = trim($texte);
        if (empty($texte)) {
            return $this->valeursParDefautEtape();
        }

        if ($this->estConfigure()) {
            try {
                $resultat = $this->appelerIaAnalyseEtape($texte);
                if (! empty($resultat)) {
                    return $resultat;
                }
            } catch (Throwable $e) {
                Log::warning('Échec appel OpenRouter analyse étape: ' . $e->getMessage());
            }
        }

        return $this->analyserEtapeFallback($texte);
    }

    private function appelerIaHistoire(Lot $lot, $etapes): ?string
    {
        $produitNom = $lot->produit?->nom ?? 'Produit alimentaire';
        $numeroLot = $lot->numero_lot;

        $descriptionEtapes = [];
        foreach ($etapes as $index => $etape) {
            $date = $etape->date_heure ? $etape->date_heure->format('d/m/Y H:i') : 'Date non précisée';
            $acteur = $etape->acteur?->nom ?? 'Acteur non précisé';
            $descriptionEtapes[] = "- Étape " . ($index + 1) . " ({$etape->typeLabel()}) le {$date} chez {$acteur} à {$etape->lieu} (Transport : {$etape->transportLabel()})" . ($etape->remarques ? " : {$etape->remarques}" : "");
        }

        $systemPrompt = "Tu es le conteur officiel de la plateforme de traçabilité alimentaire NutriTrace. Ton objectif est d'écrire un court récit captivant, chaleureux et valorisant (un seul paragraphe de 3 à 5 phrases en français impeccable) racontant l'histoire et le voyage de ce produit depuis son origine jusqu'au consommateur. Mentionne les étapes et lieux clés de façon fluide. Ne produis aucun titre, aucun préambule, uniquement le paragraphe de l'histoire.";

        $userPrompt = "Produit : {$produitNom} (Lot : {$numeroLot})\n"
            . "Parcours chronologique des étapes :\n"
            . implode("\n", $descriptionEtapes) . "\n\n"
            . "Rédige l'histoire authentique de ce produit.";

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => config('app.url', 'http://127.0.0.1:8000'),
                'X-Title' => 'NutriTrace - Traçabilité des lots',
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiUrl . '/chat/completions', [
                'model' => $this->modele,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 350,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $texte = trim((string) $response->json('choices.0.message.content'));
        return trim($texte, '"\'«» ');
    }

    private function genererHistoireFallback(Lot $lot, $etapes): string
    {
        $produitNom = $lot->produit?->nom ?? 'ce produit de qualité';
        $premiereEtape = $etapes->first();
        $derniereEtape = $etapes->count() > 1 ? $etapes->last() : null;

        $depart = $premiereEtape ? "initié chez {$premiereEtape->acteur?->nom} à {$premiereEtape->lieu}" : "soigneusement produit";
        $dateDepart = $premiereEtape?->date_heure ? " le " . $premiereEtape->date_heure->format('d/m/Y') : "";

        if ($derniereEtape && $derniereEtape->id !== $premiereEtape->id) {
            $arrivee = " Son parcours s'est ensuite poursuivi avec rigueur jusqu'à {$derniereEtape->lieu} auprès de {$derniereEtape->acteur?->nom}, acheminé par {$derniereEtape->transportLabel()}.";
        } else {
            $arrivee = " Chaque phase de sa chaîne d'approvisionnement a été scrupuleusement enregistrée.";
        }

        return "Le parcours de {$produitNom} (lot {$lot->numero_lot}) a débuté avec un passage {$depart}{$dateDepart}.{$arrivee} Un itinéraire contrôlé et transparent qui garantit fraîcheur, respect des normes d'hygiène et traçabilité exemplaire jusqu'à votre table.";
    }

    private function appelerIaAudit(Lot $lot, $etapes): ?array
    {
        $produitNom = $lot->produit?->nom ?? 'Produit';
        $numeroLot = $lot->numero_lot;
        $dateProd = $lot->date_production?->format('d/m/Y') ?? 'Non précisée';
        $datePerempt = $lot->date_peremption?->format('d/m/Y') ?? 'Non précisée';

        $descriptionEtapes = [];
        foreach ($etapes as $index => $etape) {
            $date = $etape->date_heure ? $etape->date_heure->format('d/m/Y H:i') : 'Date non précisée';
            $acteur = $etape->acteur?->nom ?? 'Acteur non précisé';
            $coords = $etape->hasCoordinates() ? " [GPS: {$etape->latitude}, {$etape->longitude}]" : "";
            $descriptionEtapes[] = "- Étape " . ($index + 1) . " : {$etape->typeLabel()} le {$date} chez {$acteur} à {$etape->lieu}{$coords}, transport: {$etape->transportLabel()}";
        }

        $systemPrompt = "Tu es un auditeur expert en traçabilité alimentaire et détection de fraudes pour NutriTrace. "
            . "Analyse la cohérence du parcours du lot : ordre des maillons (production -> transformation -> distribution -> vente), respect des dates de production et péremption, cohérence des temps de transport et vitesses de déplacement entre coordonnées géographiques (alerte téléportation), et chaîne du froid pour les produits sensibles. "
            . "Tu DOIS répondre STRICTEMENT avec un objet JSON valide (aucun texte autour). "
            . "Format JSON attendu : "
            . json_encode([
                'score' => 95,
                'statut' => 'conforme',
                'synthese' => 'Paragraphe de 2-3 phrases.',
                'anomalies' => ['Anomalie 1'],
                'points_forts' => ['Point fort 1'],
            ]);

        $userPrompt = "Produit : {$produitNom} (Lot : {$numeroLot})\n"
            . "Date production : {$dateProd} | Date péremption : {$datePerempt}\n"
            . "Parcours des étapes :\n"
            . implode("\n", $descriptionEtapes) . "\n\n"
            . "Établis le diagnostic d'audit en JSON.";

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => config('app.url', 'http://127.0.0.1:8000'),
                'X-Title' => 'NutriTrace - Audit Traçabilité',
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiUrl . '/chat/completions', [
                'model' => $this->modele,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => 600,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $rawJson = (string) $response->json('choices.0.message.content');
        $rawJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawJson));
        $rawJson = preg_replace('/\s*```$/', '', $rawJson);

        $data = json_decode($rawJson, true);

        if (! is_array($data) || ! isset($data['score'])) {
            return null;
        }

        $score = max(0, min(100, (int) $data['score']));
        $statut = in_array($data['statut'] ?? '', ['conforme', 'avertissement', 'anomalie'])
            ? $data['statut']
            : ($score >= 80 ? 'conforme' : ($score >= 50 ? 'avertissement' : 'anomalie'));

        return [
            'score' => $score,
            'statut' => $statut,
            'synthese' => trim((string) ($data['synthese'] ?? '')),
            'anomalies' => is_array($data['anomalies'] ?? null) ? array_values($data['anomalies']) : [],
            'points_forts' => is_array($data['points_forts'] ?? null) ? array_values($data['points_forts']) : [],
        ];
    }

    public function auditerParcoursFallback(Lot $lot, $etapes): array
    {
        if ($etapes->isEmpty()) {
            return [
                'score' => 50,
                'statut' => 'avertissement',
                'synthese' => "Ce lot ne possède encore aucune étape enregistrée. Ajoutez les étapes de parcours pour certifier sa traçabilité.",
                'anomalies' => ["Aucune étape de traçabilité enregistrée."],
                'points_forts' => [],
            ];
        }

        $anomalies = [];
        $pointsForts = [];
        $score = 100;

        $typesOrdre = [
            'production' => 1,
            'transformation' => 2,
            'distribution' => 3,
            'vente' => 4,
        ];

        // 1. Contrôle par rapport à la date de production
        if ($lot->date_production) {
            $premiereDate = $etapes->first()->date_heure;
            if ($premiereDate && $premiereDate->startOfDay()->lt($lot->date_production->startOfDay())) {
                $anomalies[] = "Incohérence temporelle : la première étape ({$premiereDate->format('d/m/Y')}) précède la date de production déclarée du lot ({$lot->date_production->format('d/m/Y')}).";
                $score -= 25;
            } else {
                $pointsForts[] = "Première étape cohérente avec la date de production.";
            }
        }

        // 2. Contrôle par rapport à la date de péremption
        if ($lot->date_peremption) {
            foreach ($etapes as $etape) {
                if ($etape->date_heure && $etape->date_heure->startOfDay()->gt($lot->date_peremption->startOfDay())) {
                    $anomalies[] = "Anomalie sanitaire critique : l'étape '{$etape->typeLabel()}' le {$etape->date_heure->format('d/m/Y')} est postérieure à la date de péremption du lot ({$lot->date_peremption->format('d/m/Y')}).";
                    $score -= 35;
                    break;
                }
            }
        }

        // 3. Contrôle chronologique, logique et géographique entre étapes
        $maxRangType = 0;
        $etapePrecedente = null;

        foreach ($etapes as $index => $etapeCourante) {
            $rangType = $typesOrdre[$etapeCourante->type_etape] ?? 3;

            if ($rangType < $maxRangType) {
                $typePrec = $etapePrecedente ? $etapePrecedente->typeLabel() : 'étape précédente';
                $anomalies[] = "Inversion du cycle de vie : l'étape '{$etapeCourante->typeLabel()}' est enregistrée après une étape de {$typePrec}.";
                $score -= 20;
            } else {
                $maxRangType = max($maxRangType, $rangType);
            }

            if ($etapePrecedente) {
                if ($etapeCourante->date_heure && $etapePrecedente->date_heure) {
                    if ($etapeCourante->date_heure->lt($etapePrecedente->date_heure)) {
                        $anomalies[] = "Régression temporelle : l'étape n°" . ($index + 1) . " ({$etapeCourante->date_heure->format('d/m/Y H:i')}) est antérieure à l'étape n°" . $index . " ({$etapePrecedente->date_heure->format('d/m/Y H:i')}).";
                        $score -= 25;
                    }
                }

                if ($etapePrecedente->hasCoordinates() && $etapeCourante->hasCoordinates()) {
                    $distance = $this->calculerDistanceKm(
                        (float) $etapePrecedente->latitude,
                        (float) $etapePrecedente->longitude,
                        (float) $etapeCourante->latitude,
                        (float) $etapeCourante->longitude
                    );

                    if ($etapePrecedente->date_heure && $etapeCourante->date_heure) {
                        $diffMinutes = max(1, $etapePrecedente->date_heure->diffInMinutes($etapeCourante->date_heure, false));
                        if ($diffMinutes > 0 && $distance > 25) {
                            $vitesse = round($distance / ($diffMinutes / 60));
                            if ($vitesse > 130) {
                                $anomalies[] = "Alerte vitesse/téléportation : {$distance} km parcourus en seulement {$diffMinutes} min (~{$vitesse} km/h) entre {$etapePrecedente->lieu} et {$etapeCourante->lieu}.";
                                $score -= 30;
                            }
                        }
                    }

                    if ($distance > 30 && $etapeCourante->mode_transport === 'aucun') {
                        $anomalies[] = "Incohérence transport : mode 'aucun' déclaré pour {$distance} km entre {$etapePrecedente->lieu} et {$etapeCourante->lieu}.";
                        $score -= 15;
                    }
                } elseif ($etapePrecedente->lieu !== $etapeCourante->lieu && $etapeCourante->mode_transport === 'aucun') {
                    $anomalies[] = "Changement de lieu ({$etapePrecedente->lieu} ➔ {$etapeCourante->lieu}) sans mode de transport déclaré.";
                    $score -= 10;
                }
            }

            $etapePrecedente = $etapeCourante;
        }

        // 4. Détection chaîne du froid pour denrées sensibles
        $nomProduit = mb_strtolower($lot->produit?->nom ?? '');
        $catProduit = mb_strtolower($lot->produit?->categorie?->nom ?? '');
        $estProduitSensible = (bool) preg_match('/(lait|fromag|yaourt|viand|poulet|poisson|beurr|cr[eè]m|frais|surgel)/u', $nomProduit . ' ' . $catProduit);

        if ($estProduitSensible) {
            $transportsNonFrigo = $etapes->whereIn('type_etape', ['distribution', 'vente'])->where('mode_transport', 'camion');
            if ($transportsNonFrigo->isNotEmpty()) {
                $anomalies[] = "Vigilance chaîne du froid : transport en camion ordinaire au lieu d'un camion frigorifique pour un produit sensible ({$lot->produit?->nom}).";
                $score -= 15;
            } else {
                $pointsForts[] = "Respect des conditions thermiques et de la chaîne du froid.";
            }
        }

        $score = max(0, min(100, $score));

        if ($score >= 85 && empty($anomalies)) {
            $statut = 'conforme';
            $pointsForts[] = "Ordre chronologique et étapes du cycle de vie 100% cohérents.";
            $pointsForts[] = "Déplacements géographiques et vitesses de transit réalistes.";
            $synthese = "Parcours exemplaire et certifié sans anomalie. La succession des étapes respecte scrupuleusement la réglementation agroalimentaire et assure une traçabilité totale.";
        } elseif ($score >= 60) {
            $statut = 'avertissement';
            $synthese = "Parcours globalement cohérent mais comportant " . count($anomalies) . " point(s) de vigilance à examiner par le gestionnaire qualité.";
        } else {
            $statut = 'anomalie';
            $synthese = "Anomalies critiques détectées sur la chronologie ou les déplacements de ce lot. Une vérification manuelle approfondie est requise.";
        }

        return [
            'score' => $score,
            'statut' => $statut,
            'synthese' => $synthese,
            'anomalies' => array_values(array_unique($anomalies)),
            'points_forts' => array_values(array_unique($pointsForts)),
        ];
    }

    private function appelerIaAntiGaspillage(Lot $lot): ?array
    {
        $produitNom = $lot->produit?->nom ?? 'Produit';
        $categorie = $lot->produit?->categorie?->nom ?? 'Alimentaire';
        $quantite = $lot->quantite;
        $jours = $lot->joursAvantPeremption();
        $datePerempt = $lot->date_peremption?->format('d/m/Y') ?? 'Non précisée';

        $systemPrompt = "Tu es un conseiller expert en supply chain agroalimentaire et lutte anti-gaspillage pour NutriTrace. "
            . "À partir des données du lot, préconise un plan d'action d'optimisation de stock et de déstockage adapté au délai de péremption. "
            . "Tu DOIS répondre STRICTEMENT avec un objet JSON valide (aucun texte autour). "
            . "Format JSON attendu : "
            . json_encode([
                'urgence' => 'critique',
                'statut_badge' => 'Critique J-3',
                'titre' => 'Titre de la stratégie recommandée',
                'synthese' => 'Paragraphe concis de 2 à 3 phrases.',
                'actions' => ['Action 1', 'Action 2', 'Action 3'],
            ]);

        $userPrompt = "Produit : {$produitNom} ({$categorie})\n"
            . "Quantité en stock : {$quantite} unités\n"
            . "Date de péremption : {$datePerempt} (" . ($jours !== null ? "dans {$jours} jours" : "non renseignée") . ")\n\n"
            . "Propose le plan d'action anti-gaspillage en JSON.";

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => config('app.url', 'http://127.0.0.1:8000'),
                'X-Title' => 'NutriTrace - Conseiller Anti-Gaspillage',
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiUrl . '/chat/completions', [
                'model' => $this->modele,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $rawJson = (string) $response->json('choices.0.message.content');
        $rawJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawJson));
        $rawJson = preg_replace('/\s*```$/', '', $rawJson);

        $data = json_decode($rawJson, true);

        if (! is_array($data) || empty($data['titre'])) {
            return null;
        }

        return [
            'urgence' => in_array($data['urgence'] ?? '', ['critique', 'attention', 'optimal', 'perime', 'inconnu']) ? $data['urgence'] : 'attention',
            'statut_badge' => trim((string) ($data['statut_badge'] ?? ($jours !== null ? "J-{$jours}" : 'En cours'))),
            'titre' => trim((string) ($data['titre'] ?? 'Plan d\'action anti-gaspillage')),
            'synthese' => trim((string) ($data['synthese'] ?? '')),
            'actions' => is_array($data['actions'] ?? null) ? array_values($data['actions']) : [],
        ];
    }

    public function genererConseilAntiGaspillageFallback(Lot $lot): array
    {
        $jours = $lot->joursAvantPeremption();
        $quantite = $lot->quantite;
        $nom = $lot->produit?->nom ?? 'Produit';

        if ($jours === null) {
            return [
                'urgence' => 'inconnu',
                'statut_badge' => 'Date non renseignée',
                'titre' => 'Date de péremption requise',
                'synthese' => "Veuillez renseigner la date de péremption du lot pour activer les analyses d'urgence anti-gaspillage.",
                'actions' => [
                    "Compléter la date de péremption dans la fiche du lot.",
                    "Vérifier l'étiquette DLC ou DDM sur le stock physique.",
                ],
            ];
        }

        if ($jours < 0) {
            $retard = abs($jours);
            return [
                'urgence' => 'perime',
                'statut_badge' => "Périmé (J-{$retard})",
                'titre' => 'Procédure de retrait sanitaire immédiat et valorisation',
                'synthese' => "Ce lot de {$quantite} unités de {$nom} a dépassé sa date limite ({$lot->date_peremption?->format('d/m/Y')}). Conformément aux règles sanitaires, il ne peut plus être commercialisé.",
                'actions' => [
                    "Consigner et isoler immédiatement les {$quantite} unités en zone de non-conformité.",
                    "Valorisation écologique : orienter vers la filière méthanisation ou compostage agricole agréée.",
                    "Enregistrer le procès-verbal de destruction ou de déclassement pour la traçabilité.",
                ],
            ];
        }

        if ($jours <= 7) {
            return [
                'urgence' => 'critique',
                'statut_badge' => "Critique J-{$jours}",
                'titre' => "Plan d'action anti-gaspillage d'urgence (J-{$jours})",
                'synthese' => "Urgence maximale : il ne reste que {$jours} jour(s) pour écouler ce lot de {$quantite} unités de {$nom}. Une réaction coordonnée dans les prochaines 24h est essentielle pour éviter le gaspillage.",
                'actions' => [
                    "Démarque immédiate : appliquer un étiquetage promo anti-gaspi (-30% à -50%) et placer en tête de gondole.",
                    "Plateformes partenaires : publier les stocks restants sur les applications de paniers anti-gaspillage.",
                    "Don solidaire : réserver une part du stock pour collecte par une association caritative locale sous 48h.",
                ],
            ];
        }

        if ($jours <= 30) {
            return [
                'urgence' => 'attention',
                'statut_badge' => "Attention J-{$jours}",
                'titre' => "Optimisation de rotation de stock (J-{$jours})",
                'synthese' => "Le lot entre dans son dernier mois de conservation ({$jours} jours restants, {$quantite} unités). Il convient d'accélérer la rotation des stocks pour préserver la marge.",
                'actions' => [
                    "Règle FEFO (First Expired, First Out) : avancer ce lot prioritairement devant les réceptions récentes.",
                    "Opération promotionnelle ciblée : mettre en place une offre groupée ('Le 2ème à moitié prix') en magasin.",
                    "Ajuster le prochain réapprovisionnement pour tenir compte de la vitesse de vente actuelle.",
                ],
            ];
        }

        return [
            'urgence' => 'optimal',
            'statut_badge' => "Optimal (J+{$jours})",
            'titre' => 'Stock sain — Rotation standard maîtrisée',
            'synthese' => "Ce lot bénéficie d'une durée de vie résiduelle confortable ({$jours} jours restants avant échéance). Aucun risque de perte à court terme.",
            'actions' => [
                "Poursuivre la commercialisation standard au tarif normal.",
                "Maintenir les contrôles de température et les conditions de stockage prescrites.",
                "Planifier le réassort suivant selon le calendrier habituel.",
            ],
        ];
    }

    private function calculerDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 1);
    }

    private function appelerIaAnalyseEtape(string $texte): ?array
    {
        $typesValides = array_keys(config('nutritrace.options.etape_types', []));
        $transportsValides = array_keys(config('nutritrace.options.modes_transport', []));

        $systemPrompt = "Tu es un assistant d'extraction d'entités pour une application de logistique et traçabilité alimentaire. "
            . "À partir de la phrase de l'utilisateur, extrais les détails de l'étape. "
            . "Règles strictes pour le type d'étape : "
            . "- 'production' : pour toute mention de production ('produit', 'produite', 'récolte', 'cueillette', 'culture', 'semis', 'élevage', 'ferme', 'champ'). "
            . "- 'transformation' : pour toute mention de transformation, usinage, fabrication, moulin, pressage, emballage. "
            . "- 'distribution' : pour toute mention de livraison, transport, acheminement, expédition, entrepôt. "
            . "- 'vente' : pour toute mention de vente, magasin, rayon, supermarché, commercialisation. "
            . "Tu DOIS répondre STRICTEMENT avec un objet JSON valide (aucun texte autour). "
            . "Format JSON attendu : "
            . json_encode([
                'type_etape' => 'un parmi : ' . implode(', ', $typesValides),
                'lieu' => 'nom de la ville ou du lieu exact',
                'date_heure' => 'YYYY-MM-DDTHH:mm (si heure absente, mettre 08:00)',
                'mode_transport' => 'un parmi : ' . implode(', ', $transportsValides),
                'remarques' => 'remarques ou vide'
            ]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => config('app.url', 'http://127.0.0.1:8000'),
                'X-Title' => 'NutriTrace - Assistant Étape',
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiUrl . '/chat/completions', [
                'model' => $this->modele,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $texte],
                ],
                'temperature' => 0.1,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $rawJson = (string) $response->json('choices.0.message.content');
        $rawJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawJson));
        $rawJson = preg_replace('/\s*```$/', '', $rawJson);

        $data = json_decode($rawJson, true);

        if (! is_array($data)) {
            return null;
        }

        return [
            'type_etape' => in_array($data['type_etape'] ?? '', $typesValides) ? $data['type_etape'] : 'distribution',
            'lieu' => substr(trim((string) ($data['lieu'] ?? '')), 0, 140),
            'date_heure' => ! empty($data['date_heure']) ? Carbon::parse($data['date_heure'])->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i'),
            'mode_transport' => in_array($data['mode_transport'] ?? '', $transportsValides) ? $data['mode_transport'] : 'camion',
            'remarques' => trim((string) ($data['remarques'] ?? '')),
        ];
    }

    private function analyserEtapeFallback(string $texte): array
    {
        $texteLower = mb_strtolower($texte);

        // Détection Type d'étape
        $typeEtape = 'distribution';
        if (preg_match('/(transfor|usin|fabriq|moul|press|conditionn|emball|prepar)/u', $texteLower)) {
            $typeEtape = 'transformation';
        } elseif (preg_match('/(produi[ts]?|product|r[eé]colt|cueill|semis|plant|cultiv|ferme|champ|[eé]levage|[eé]lev[eé]|agricol|origin)/u', $texteLower)) {
            $typeEtape = 'production';
        } elseif (preg_match('/(vent|magasin|rayon|boutiq|superm|client|commercialis)/u', $texteLower)) {
            $typeEtape = 'vente';
        } elseif (preg_match('/(livr|transp|achemin|exp[eé]d|d[eé]p[oô]t|entrep[oô]t|stock|reception)/u', $texteLower)) {
            $typeEtape = 'distribution';
        }

        // Détection Mode de transport
        $transport = 'camion';
        if (preg_match('/(frigo|r[eé]frig[eé]r[eé]|temp[eé]rature dirig[eé]e)/u', $texteLower)) {
            $transport = 'camion_frigorifique';
        } elseif (preg_match('/(train|ferroviaire|rail)/u', $texteLower)) {
            $transport = 'train';
        } elseif (preg_match('/(bateau|navire|maritime|mer|port)/u', $texteLower)) {
            $transport = 'bateau';
        } elseif (preg_match('/(aucun|sans transport|sur place|imm[eé]diat)/u', $texteLower)) {
            $transport = 'aucun';
        }

        // Détection Date (hier, avant-hier, mois en lettres ou format JJ/MM/AAAA)
        $date = now();
        $moisFrancais = [
            'janvier' => 1, 'fevrier' => 2, 'février' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8, 'août' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12, 'décembre' => 12,
        ];

        if (preg_match('/\bhier\b/u', $texteLower)) {
            $date = now()->subDay();
        } elseif (preg_match('/avant[\s-]hier/u', $texteLower)) {
            $date = now()->subDays(2);
        } elseif (preg_match('/(\d{1,2})\s+(janvier|f[eé]vrier|mars|avril|mai|juin|juillet|ao[uû]t|septembre|octobre|novembre|d[eé]cembre)\s*(\d{4})?/ui', $texte, $mDateText)) {
            $jour = (int) $mDateText[1];
            $nomMois = mb_strtolower($mDateText[2]);
            $annee = ! empty($mDateText[3]) ? (int) $mDateText[3] : (int) now()->year;
            $moisNum = $moisFrancais[$nomMois] ?? 10;
            try {
                $date = Carbon::createFromDate($annee, $moisNum, $jour);
            } catch (Throwable) {}
        } elseif (preg_match('/(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})/', $texte, $m)) {
            try {
                $annee = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                $date = Carbon::createFromDate((int) $annee, (int) $m[2], (int) $m[1]);
            } catch (Throwable) {}
        }

        // Détection Heure (ex: 14h, 14h30 ou 14:30)
        $heure = 10;
        $minute = 0;
        if (preg_match('/(\d{1,2})\s*(?:h|:)\s*(\d{2})?/u', $texteLower, $mHeure)) {
            $heure = (int) $mHeure[1];
            $minute = isset($mHeure[2]) ? (int) $mHeure[2] : 0;
        }

        $dateHeure = $date->setTime($heure, $minute)->format('Y-m-d\TH:i');

        // Détection Lieu
        $lieu = '';
        $texteSansHeure = preg_replace('/\b[aà]\s+\d{1,2}(?:h\d*|:\d*)\b/ui', '', $texte);

        if (preg_match('/(?:[aà]|vers|chez|en)\s+([A-Za-zÀ-ÿ0-9\s\-]+?)(?=\s+(?:hier|aujourd|par|le|la|les|avec|sous|\d{1,2}[\/\.-]|\d{1,2}\s+(?:janvier|f[eé]vrier|mars|avril|mai|juin|juillet|ao[uû]t|septembre|octobre|novembre|d[eé]cembre))|\s*[,.]|\s*$)/ui', $texteSansHeure, $mLieu)) {
            $lieu = trim($mLieu[1]);
        }
        if (empty($lieu) && preg_match('/(?:usine|entrepôt|entrepot|magasin|port|ferme)\s+(?:de\s+)?([A-Za-zÀ-ÿ0-9\s\-]+)/ui', $texte, $mLieu2)) {
            $lieu = trim($mLieu2[0]);
        }
        if (empty($lieu)) {
            $lieu = 'Tunis';
        }

        return [
            'type_etape' => $typeEtape,
            'lieu' => substr($lieu, 0, 140),
            'date_heure' => $dateHeure,
            'mode_transport' => $transport,
            'remarques' => 'Saisie assistée par IA.',
        ];
    }

    private function valeursParDefautEtape(): array
    {
        return [
            'type_etape' => 'distribution',
            'lieu' => '',
            'date_heure' => now()->format('Y-m-d\TH:i'),
            'mode_transport' => 'camion',
            'remarques' => '',
        ];
    }

    /**
     * Analyse un texte en langage naturel pour préremplir un formulaire de création de lot.
     *
     * @return array{
     *     produit_id: int|null,
     *     quantite: int|null,
     *     date_production: string,
     *     date_peremption: string|null
     * }
     */
    public function analyserTexteLot(string $texte, $produits = null): array
    {
        $texte = trim($texte);
        $texteLower = mb_strtolower($texte);

        // 1. Détection de la quantité
        $quantite = 100;
        if (preg_match('/(\d{1,7})\s*(?:unit[eé]s?|kg|litres?|l\b|bouteilles?|pots?|pi[eè]ces?|paquets?|bo[iî]tes?|sacs?)?/ui', $texteLower, $mQte)) {
            $quantite = (int) $mQte[1];
        }

        // 2. Détection Produit si fourni
        $produitId = null;
        if ($produits && count($produits) > 0) {
            foreach ($produits as $prod) {
                $nomProd = mb_strtolower($prod->nom ?? '');
                if (! empty($nomProd) && str_contains($texteLower, $nomProd)) {
                    $produitId = $prod->id;
                    break;
                }
            }
            if (! $produitId) {
                foreach ($produits as $prod) {
                    $mots = preg_split('/\s+/', mb_strtolower($prod->nom ?? ''));
                    foreach ($mots as $mot) {
                        if (mb_strlen($mot) >= 4 && str_contains($texteLower, $mot)) {
                            $produitId = $prod->id;
                            break 2;
                        }
                    }
                }
            }
        }

        // 3. Détection Date Production
        $dateProd = today();
        if (preg_match('/\bhier\b/u', $texteLower)) {
            $dateProd = today()->subDay();
        } elseif (preg_match('/avant[\s-]hier/u', $texteLower)) {
            $dateProd = today()->subDays(2);
        } elseif (preg_match('/(?:produit|fabriqu[eé]|r[eé]colt[eé]|le)\s+(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})/ui', $texte, $mDateP)) {
            try {
                $annee = strlen($mDateP[3]) === 2 ? '20' . $mDateP[3] : $mDateP[3];
                $dateProd = Carbon::createFromDate((int) $annee, (int) $mDateP[2], (int) $mDateP[1]);
            } catch (Throwable) {}
        }

        // 4. Détection Date Péremption
        $datePerempt = null;
        if (preg_match('/(?:expir|p[eé]rim|dlc|ddm|valable|jusqu)\D{0,15}(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})/ui', $texte, $mDateE)) {
            try {
                $annee = strlen($mDateE[3]) === 2 ? '20' . $mDateE[3] : $mDateE[3];
                $datePerempt = Carbon::createFromDate((int) $annee, (int) $mDateE[2], (int) $mDateE[1])->format('Y-m-d');
            } catch (Throwable) {}
        } elseif (preg_match('/(?:dans|sous)\s+(\d{1,3})\s+(mois|jours?|ans?|semaines?)/ui', $texteLower, $mDelai)) {
            $nb = (int) $mDelai[1];
            $unite = $mDelai[2];
            $d = $dateProd->copy();
            if (str_starts_with($unite, 'jour')) $d->addDays($nb);
            elseif (str_starts_with($unite, 'semaine')) $d->addWeeks($nb);
            elseif (str_starts_with($unite, 'mois')) $d->addMonths($nb);
            elseif (str_starts_with($unite, 'an')) $d->addYears($nb);
            $datePerempt = $d->format('Y-m-d');
        }

        if (! $datePerempt) {
            $datePerempt = $dateProd->copy()->addMonths(6)->format('Y-m-d');
        }

        return [
            'produit_id' => $produitId,
            'quantite' => $quantite,
            'date_production' => $dateProd->format('Y-m-d'),
            'date_peremption' => $datePerempt,
        ];
    }
}
