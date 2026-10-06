<?php

namespace App\Support;

use App\Models\Acteur;
use App\Models\Analyse;
use App\Models\Certification;
use App\Models\EmpreinteCarbone;
use App\Models\Etape;
use App\Models\Lot;
use App\Models\Signalement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Statistiques du tableau de bord administrateur, calculées uniquement à partir des données enregistrées.
 */
final class TableauDeBord
{
    /**
     * Métriques de la courbe d'évolution : table et colonne de date métier utilisées.
     */
    private const METRIQUES = [
        'lots' => ['libelle' => 'Lots produits', 'legende' => 'lots produits', 'table' => 'lots', 'colonne' => 'date_production'],
        'etapes' => ['libelle' => 'Étapes de parcours', 'legende' => 'étapes enregistrées', 'table' => 'etapes', 'colonne' => 'date_heure'],
        'certifications' => ['libelle' => 'Certifications obtenues', 'legende' => 'certifications obtenues', 'table' => 'certifications', 'colonne' => 'date_obtention'],
        'analyses' => ['libelle' => 'Analyses qualité', 'legende' => 'analyses prélevées', 'table' => 'analyses', 'colonne' => 'date_prelevement'],
        'scans' => ['libelle' => 'Scans consommateurs', 'legende' => 'scans de lots', 'table' => 'scans', 'colonne' => 'created_at'],
        'acteurs' => ['libelle' => 'Nouveaux acteurs', 'legende' => 'acteurs inscrits', 'table' => 'acteurs', 'colonne' => 'created_at'],
    ];

    public const COULEURS_ETAPES = [
        'production' => '#064E35',
        'transformation' => '#087443',
        'distribution' => '#4DAA78',
        'vente' => '#A8D5BA',
        'aucune' => '#D5DDD7',
    ];

    public const COULEURS_CERTIFICATIONS = [
        'valide' => '#087443',
        'en_attente' => '#FFB627',
        'expiree' => '#B8C4BC',
        'suspendue' => '#D0644F',
    ];

    private readonly Periode $precedente;

    public function __construct(private readonly Periode $periode)
    {
        $this->precedente = $periode->precedente();
    }

    public function precedente(): Periode
    {
        return $this->precedente;
    }

    /**
     * @return list<string>
     */
    public function libelles(bool $longs = false, ?Periode $periode = null): array
    {
        return array_column(($periode ?? $this->periode)->compartiments(), $longs ? 'libelleLong' : 'libelle');
    }

    /**
     * Les quatre indicateurs clés, comparés à la période précédente.
     *
     * @return list<array<string, mixed>>
     */
    public function kpis(): array
    {
        $lots = $this->serie('lots', 'date_production', $this->periode);
        $lotsAvant = $this->total('lots', 'date_production', $this->precedente);

        [$acteursSerie, $acteurs] = $this->acteursActifs($this->periode);
        [, $acteursAvant] = $this->acteursActifs($this->precedente);

        $certifications = $this->serie('certifications', 'date_obtention', $this->periode);
        $certificationsAvant = $this->total('certifications', 'date_obtention', $this->precedente);

        $co2 = $this->co2($this->periode);
        $co2Avant = $this->co2($this->precedente);

        return [
            [
                'libelle' => 'Lots produits',
                'valeur' => array_sum($lots),
                'affichage' => self::nombre(array_sum($lots)),
                'unite' => null,
                'precedent' => $lotsAvant,
                'inverse' => false,
                'contexte' => self::nombre(Lot::count()).' lots tracés au total',
                'serie' => $lots,
                'lien' => route('admin.lots.index'),
            ],
            [
                'libelle' => 'Acteurs actifs',
                'valeur' => $acteurs,
                'affichage' => self::nombre($acteurs),
                'unite' => null,
                'precedent' => $acteursAvant,
                'inverse' => false,
                'contexte' => 'sur '.self::nombre(Acteur::count()).' acteurs · au moins une étape saisie',
                'serie' => $acteursSerie,
                'lien' => route('admin.acteurs.index'),
            ],
            [
                'libelle' => 'Certifications obtenues',
                'valeur' => array_sum($certifications),
                'affichage' => self::nombre(array_sum($certifications)),
                'unite' => null,
                'precedent' => $certificationsAvant,
                'inverse' => false,
                'contexte' => self::nombre(Certification::valides()->count()).' valides aujourd\'hui',
                'serie' => $certifications,
                'lien' => route('admin.certifications.index'),
            ],
            [
                'libelle' => 'Empreinte moyenne',
                'valeur' => $co2['moyenne'],
                'affichage' => $co2['moyenne'] === null ? '—' : self::nombre($co2['moyenne'], 2),
                'unite' => $co2['moyenne'] === null ? null : 'kg CO₂e',
                'precedent' => $co2Avant['moyenne'],
                'inverse' => true,
                'contexte' => $co2['moyenne'] === null
                    ? 'Aucune empreinte calculée sur la période'
                    : 'par unité · score moyen '.EmpreinteCarbone::scorePour($co2['moyenne']),
                'serie' => $co2['serie'],
                'lien' => route('admin.empreintes.index'),
            ],
        ];
    }

    /**
     * Séries de la courbe d'évolution pour chaque métrique (période actuelle et précédente).
     *
     * @return array<string, array<string, mixed>>
     */
    public function tendances(): array
    {
        return collect(self::METRIQUES)->map(function (array $m) {
            $courant = $this->serie($m['table'], $m['colonne'], $this->periode);
            $precedent = $this->serie($m['table'], $m['colonne'], $this->precedente);

            return [
                'libelle' => $m['libelle'],
                'legende' => $m['legende'],
                'courant' => $courant,
                'precedent' => $precedent,
                'total' => array_sum($courant),
                'totalPrecedent' => array_sum($precedent),
                'variation' => self::variation(array_sum($courant), array_sum($precedent)),
            ];
        })->all();
    }

    /**
     * Situation actuelle de tous les lots, selon le type de leur dernière étape enregistrée.
     *
     * @return array{total: int, segments: list<array{cle: string, libelle: string, total: int, couleur: string}>}
     */
    public function repartitionLots(): array
    {
        $derniere = Etape::query()
            ->select('type_etape')
            ->whereColumn('etapes.lot_id', 'lots.id')
            ->orderByDesc('date_heure')
            ->orderByDesc('id')
            ->limit(1);

        $parEtape = DB::query()
            ->fromSub(Lot::query()->select('lots.id')->selectSub($derniere, 'etape'), 'situation')
            ->selectRaw('etape, COUNT(*) as total')
            ->groupBy('etape')
            ->pluck('total', 'etape');

        $libelles = config('nutritrace.options.etape_types') + ['aucune' => 'Sans étape'];

        $segments = collect($libelles)->map(fn (string $libelle, string $cle) => [
            'cle' => $cle,
            'libelle' => $libelle,
            'total' => (int) ($cle === 'aucune' ? ($parEtape[''] ?? 0) : ($parEtape[$cle] ?? 0)),
            'couleur' => self::COULEURS_ETAPES[$cle] ?? '#B8C4BC',
        ])->filter(fn (array $s) => $s['total'] > 0)->values()->all();

        return ['total' => array_sum(array_column($segments, 'total')), 'segments' => $segments];
    }

    /**
     * Certifications par type et par statut effectif (une certification valide dont la date est passée est expirée).
     *
     * @return array{types: list<string>, statuts: list<array{cle: string, libelle: string, couleur: string, valeurs: list<int>}>, total: int, valides: int}
     */
    public function certifications(): array
    {
        $statutEffectif = "CASE WHEN statut = 'valide' AND date_expiration < ? THEN 'expiree' ELSE statut END";

        $lignes = DB::table('certifications')
            ->selectRaw("type, {$statutEffectif} as statut_effectif, COUNT(*) as total", [today()->toDateString()])
            ->groupBy('type', 'statut_effectif')
            ->get();

        $types = collect(config('nutritrace.options.certification_types'))
            ->filter(fn (string $libelle, string $cle) => $lignes->contains('type', $cle));

        $statuts = collect(config('nutritrace.options.certification_statuts'))
            ->map(fn (string $libelle, string $cle) => [
                'cle' => $cle,
                'libelle' => $libelle,
                'couleur' => self::COULEURS_CERTIFICATIONS[$cle] ?? '#B8C4BC',
                'valeurs' => $types->keys()->map(fn (string $type) => (int) $lignes
                    ->where('type', $type)->where('statut_effectif', $cle)->sum('total'))->all(),
            ])
            ->filter(fn (array $s) => array_sum($s['valeurs']) > 0)
            ->values()->all();

        return [
            'types' => $types->values()->all(),
            'statuts' => $statuts,
            'total' => (int) $lignes->sum('total'),
            'valides' => (int) $lignes->where('statut_effectif', 'valide')->sum('total'),
        ];
    }

    /**
     * Empreinte carbone moyenne par unité sur la période, son évolution et la répartition des scores.
     *
     * @return array<string, mixed>
     */
    public function environnement(): array
    {
        $courant = $this->co2($this->periode);
        $precedent = $this->co2($this->precedente);

        $scores = DB::table('empreinte_carbones')
            ->whereBetween('date_calcul', $this->periode->bornes())
            ->selectRaw('score, COUNT(*) as total')
            ->groupBy('score')
            ->pluck('total', 'score');

        return [
            'moyenne' => $courant['moyenne'],
            'precedente' => $precedent['moyenne'],
            'nombre' => $courant['nombre'],
            'serie' => $courant['serie'],
            'scores' => collect(['A', 'B', 'C', 'D', 'E'])->mapWithKeys(fn (string $s) => [$s => (int) ($scores[$s] ?? 0)])->all(),
        ];
    }

    /**
     * Derniers événements de la période : étapes, certifications, analyses et signalements.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function activite(int $limite = 8): Collection
    {
        [$debut, $fin] = $this->periode->bornes();
        $types = config('nutritrace.options.etape_types');

        $etapes = Etape::query()
            ->with(['acteur:id,nom', 'lot:id,numero_lot'])
            ->whereBetween('date_heure', [$debut, $fin])
            ->latest('date_heure')->limit($limite)->get()
            ->map(fn (Etape $e) => [
                'date' => $e->date_heure,
                'heure' => true,
                'icone' => Etape::ICONES[$e->type_etape] ?? 'bi-signpost',
                'type' => 'Étape · '.($types[$e->type_etape] ?? $e->type_etape),
                'acteur' => $e->acteur?->nom,
                'objet' => $e->lot?->numero_lot,
                'statut' => 'Enregistrée',
                'badge' => 'nt-badge',
                'lien' => route('admin.etapes.show', $e),
            ]);

        $certifications = Certification::query()
            ->with(['organisme:id,nom', 'produit:id,nom'])
            ->whereBetween('date_obtention', [$debut, $fin])
            ->latest('date_obtention')->limit($limite)->get()
            ->map(fn (Certification $c) => [
                'date' => $c->date_obtention,
                'heure' => false,
                'icone' => $c->icone(),
                'type' => 'Certification · '.$c->typeLabel(),
                'acteur' => $c->organisme?->nom,
                'objet' => $c->produit?->nom,
                'statut' => $c->statutLabel(),
                'badge' => match ($c->statutEffectif()) {
                    'valide' => 'nt-badge',
                    'en_attente' => 'nt-badge nt-badge-gold',
                    'suspendue' => 'nt-badge nt-badge-danger',
                    default => 'nt-badge nt-badge-muted',
                },
                'lien' => route('admin.certifications.show', $c),
            ]);

        $analyses = Analyse::query()
            ->with(['laboratoire:id,nom', 'lot:id,numero_lot'])
            ->whereBetween('date_prelevement', [$debut, $fin])
            ->latest('date_prelevement')->limit($limite)->get()
            ->map(fn (Analyse $a) => [
                'date' => $a->date_prelevement,
                'heure' => false,
                'icone' => $a->icone(),
                'type' => 'Analyse · '.$a->typeLabel(),
                'acteur' => $a->laboratoire?->nom,
                'objet' => $a->lot?->numero_lot,
                'statut' => $a->resultatLabel(),
                'badge' => match ($a->resultat) {
                    'conforme' => 'nt-badge',
                    'non_conforme' => 'nt-badge nt-badge-danger',
                    default => 'nt-badge nt-badge-gold',
                },
                'lien' => route('admin.analyses.show', $a),
            ]);

        $signalements = Signalement::query()
            ->with(['user:id,name', 'produit:id,nom'])
            ->whereBetween('created_at', [$debut, $fin])
            ->latest()->limit($limite)->get()
            ->map(fn (Signalement $s) => [
                'date' => $s->created_at,
                'heure' => true,
                'icone' => 'bi-flag',
                'type' => 'Signalement · '.$s->motifLabel(),
                'acteur' => $s->user?->name,
                'objet' => $s->produit?->nom,
                'statut' => $s->statutLabel(),
                'badge' => match ($s->statut) {
                    'valide' => 'nt-badge',
                    'rejete' => 'nt-badge nt-badge-muted',
                    default => 'nt-badge nt-badge-gold',
                },
                'lien' => $s->produit_id ? route('admin.produits.show', $s->produit_id) : null,
            ]);

        return $etapes->concat($certifications)->concat($analyses)->concat($signalements)
            ->sortByDesc(fn (array $ligne) => $ligne['date']?->getTimestamp() ?? 0)
            ->take($limite)
            ->values();
    }

    /**
     * Problèmes réellement détectés dans les données, du plus grave au moins grave.
     *
     * @return list<array<string, mixed>>
     */
    public function alertes(): array
    {
        $alertes = [];
        $aujourdhui = today();

        $nonConformes = Analyse::resultat('non_conforme');
        if ($n = $nonConformes->count()) {
            $alertes[] = [
                'niveau' => 'critique',
                'icone' => 'bi-x-octagon',
                'titre' => $n.' analyse'.($n > 1 ? 's' : '').' non conforme'.($n > 1 ? 's' : ''),
                'description' => 'Un résultat non conforme concerne un lot en circulation.',
                'date' => self::date($nonConformes->max('date_resultat') ?? $nonConformes->max('date_prelevement')),
                'dateLibelle' => 'Dernier résultat',
                'action' => ['libelle' => 'Voir les analyses', 'url' => route('admin.analyses.index', ['resultat' => 'non_conforme'])],
            ];
        }

        $expirent = Certification::valides()->whereDate('date_expiration', '<=', $aujourdhui->copy()->addDays(30));
        if ($n = $expirent->count()) {
            $alertes[] = [
                'niveau' => 'attention',
                'icone' => 'bi-hourglass-split',
                'titre' => $n.' certification'.($n > 1 ? 's expirent' : ' expire').' sous 30 jours',
                'description' => 'Le label ne sera plus affiché sur la fiche produit après expiration.',
                'date' => self::date($expirent->min('date_expiration')),
                'dateLibelle' => 'Première échéance',
                'action' => ['libelle' => 'Voir les certifications', 'url' => route('admin.certifications.index', ['statut' => 'valide'])],
            ];
        }

        $enAttente = Certification::where('statut', 'en_attente');
        if ($n = $enAttente->count()) {
            $alertes[] = [
                'niveau' => 'attention',
                'icone' => 'bi-patch-question',
                'titre' => $n.' certification'.($n > 1 ? 's' : '').' à valider',
                'description' => 'Demandes déposées par les professionnels, en attente de vérification.',
                'date' => self::date($enAttente->min('created_at')),
                'dateLibelle' => 'Plus ancienne demande',
                'action' => ['libelle' => 'Vérifier', 'url' => route('admin.certifications.index', ['statut' => 'en_attente'])],
            ];
        }

        $analysesEnAttente = Analyse::resultat('en_attente');
        if ($n = $analysesEnAttente->count()) {
            $alertes[] = [
                'niveau' => 'attention',
                'icone' => 'bi-clipboard-pulse',
                'titre' => $n.' analyse'.($n > 1 ? 's' : '').' sans résultat',
                'description' => 'Prélèvement effectué, résultat du laboratoire non encore saisi.',
                'date' => self::date($analysesEnAttente->min('date_prelevement')),
                'dateLibelle' => 'Plus ancien prélèvement',
                'action' => ['libelle' => 'Compléter', 'url' => route('admin.analyses.index', ['resultat' => 'en_attente'])],
            ];
        }

        $signalements = Signalement::where('statut', 'en_attente');
        if ($n = $signalements->count()) {
            $alertes[] = [
                'niveau' => 'attention',
                'icone' => 'bi-flag',
                'titre' => $n.' signalement'.($n > 1 ? 's' : '').' consommateur'.($n > 1 ? 's' : '').' en attente',
                'description' => 'Traités par les professionnels propriétaires des produits concernés.',
                'date' => self::date($signalements->min('created_at')),
                'dateLibelle' => 'Plus ancien',
                'action' => null,
            ];
        }

        $sansEtape = Lot::doesntHave('etapes');
        if ($n = $sansEtape->count()) {
            $alertes[] = [
                'niveau' => 'info',
                'icone' => 'bi-signpost-split',
                'titre' => $n.' lot'.($n > 1 ? 's' : '').' sans étape de parcours',
                'description' => 'Traçabilité incomplète : aucun parcours visible pour le consommateur.',
                'date' => self::date($sansEtape->min('date_production')),
                'dateLibelle' => 'Produit le',
                'action' => ['libelle' => 'Voir les lots', 'url' => route('admin.lots.index')],
            ];
        }

        $sansEmpreinte = Lot::has('etapes')->doesntHave('empreinteCarbone');
        if ($n = $sansEmpreinte->count()) {
            $alertes[] = [
                'niveau' => 'info',
                'icone' => 'bi-cloud-slash',
                'titre' => $n.' lot'.($n > 1 ? 's' : '').' sans empreinte carbone',
                'description' => 'Donnée manquante : aucun indicateur environnemental déclaré.',
                'date' => self::date($sansEmpreinte->min('date_production')),
                'dateLibelle' => 'Produit le',
                'action' => ['libelle' => 'Calculer', 'url' => route('admin.empreintes.create')],
            ];
        }

        return $alertes;
    }

    /**
     * Indice de traçabilité : part moyenne des lots disposant d'un parcours, d'une empreinte et d'une analyse.
     *
     * @return array<string, mixed>|null
     */
    public function indice(): ?array
    {
        $total = Lot::count();

        if ($total === 0) {
            return null;
        }

        $criteres = [
            ['libelle' => 'Parcours renseigné', 'total' => Lot::has('etapes')->count()],
            ['libelle' => 'Empreinte calculée', 'total' => Lot::has('empreinteCarbone')->count()],
            ['libelle' => 'Analyse qualité', 'total' => Lot::has('analyses')->count()],
        ];

        $score = (int) round(array_sum(array_column($criteres, 'total')) / (count($criteres) * $total) * 100);

        return [
            'score' => $score,
            'niveau' => match (true) {
                $score >= 85 => 'Excellent',
                $score >= 70 => 'Bon',
                $score >= 50 => 'À améliorer',
                default => 'Insuffisant',
            },
            'lots' => $total,
            'criteres' => array_map(fn (array $c) => $c + ['pourcentage' => (int) round($c['total'] / $total * 100)], $criteres),
        ];
    }

    public static function variation(int|float|null $courant, int|float|null $precedent): ?float
    {
        if ($courant === null || $precedent === null || (float) $precedent === 0.0) {
            return null;
        }

        return round(($courant - $precedent) / $precedent * 100, 1);
    }

    public static function nombre(int|float $valeur, int $decimales = 0): string
    {
        return number_format($valeur, $decimales, ',', ' ');
    }

    /**
     * @return list<int>
     */
    private function serie(string $table, string $colonne, Periode $periode): array
    {
        $valeurs = array_fill(0, count($periode->compartiments()), 0);

        foreach ($this->parJour($table, $colonne, $periode) as $jour => $total) {
            if (($index = $periode->indexDe($jour)) !== null) {
                $valeurs[$index] += $total;
            }
        }

        return $valeurs;
    }

    private function total(string $table, string $colonne, Periode $periode): int
    {
        return DB::table($table)->whereBetween($colonne, $periode->bornes())->count();
    }

    /**
     * @return array<string, int>
     */
    private function parJour(string $table, string $colonne, Periode $periode): array
    {
        return DB::table($table)
            ->whereBetween($colonne, $periode->bornes())
            ->selectRaw("DATE({$colonne}) as jour, COUNT(*) as total")
            ->groupByRaw("DATE({$colonne})")
            ->pluck('total', 'jour')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * Acteurs distincts ayant saisi au moins une étape, par compartiment et sur toute la période.
     *
     * @return array{0: list<int>, 1: int}
     */
    private function acteursActifs(Periode $periode): array
    {
        $lignes = DB::table('etapes')
            ->whereBetween('date_heure', $periode->bornes())
            ->selectRaw('DATE(date_heure) as jour, acteur_id')
            ->distinct()
            ->get();

        $parCompartiment = array_fill(0, count($periode->compartiments()), []);

        foreach ($lignes as $ligne) {
            if (($index = $periode->indexDe($ligne->jour)) !== null) {
                $parCompartiment[$index][$ligne->acteur_id] = true;
            }
        }

        return [array_map('count', $parCompartiment), $lignes->pluck('acteur_id')->unique()->count()];
    }

    /**
     * @return array{moyenne: ?float, nombre: int, serie: list<?float>}
     */
    private function co2(Periode $periode): array
    {
        $lignes = DB::table('empreinte_carbones')
            ->whereBetween('date_calcul', $periode->bornes())
            ->selectRaw('DATE(date_calcul) as jour, COUNT(*) as nombre, SUM(co2_total) as somme')
            ->groupByRaw('DATE(date_calcul)')
            ->get();

        $compartiments = array_fill(0, count($periode->compartiments()), ['nombre' => 0, 'somme' => 0.0]);

        foreach ($lignes as $ligne) {
            if (($index = $periode->indexDe($ligne->jour)) !== null) {
                $compartiments[$index]['nombre'] += (int) $ligne->nombre;
                $compartiments[$index]['somme'] += (float) $ligne->somme;
            }
        }

        $nombre = (int) $lignes->sum('nombre');

        return [
            'moyenne' => $nombre ? round($lignes->sum('somme') / $nombre, 2) : null,
            'nombre' => $nombre,
            'serie' => array_map(fn (array $c) => $c['nombre'] ? round($c['somme'] / $c['nombre'], 2) : null, $compartiments),
        ];
    }

    private static function date(mixed $valeur): ?CarbonImmutable
    {
        return $valeur ? CarbonImmutable::parse($valeur) : null;
    }
}
