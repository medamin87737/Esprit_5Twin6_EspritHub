<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Module 1 — Catalogue public enrichi (Ghada).
 *
 * Supporte deux modes :
 *  - Rendu complet (GET /produits)  → retourne la vue complète
 *  - Fragment AJAX (Accept: application/json ou ?_fragment=1) → retourne JSON
 *    avec { html: '<div>…</div>', total: 42, page: 1, derniere_page: 4 }
 *    pour remplacer la zone de résultats sans rechargement.
 *
 * Le formulaire fonctionne sans JavaScript (POST classique).
 */
class CatalogueController extends Controller
{
    /** Nombre de produits par page. */
    private const PAR_PAGE = 12;

    /** Tris disponibles. */
    private const TRIS = ['recents', 'eco_score', 'labels', 'nom'];

    public function __invoke(Request $request): View|JsonResponse
    {
        $filtres = $this->validerFiltres($request);

        $produits = $this->construireRequete($filtres)
            ->paginate(self::PAR_PAGE)
            ->withQueryString();

        // Données partagées entre rendu complet et fragment AJAX
        $categories = Categorie::orderBy('nom')->get();
        $repartition = Produit::repartitionScores();
        $filtresActifs = $this->filtresActifs($filtres, $categories);

        // Mode fragment AJAX : on retourne uniquement le JSON
        if ($request->expectsJson() || $request->boolean('_fragment')) {
            $html = view('pages.front.produits._resultats', [
                'produits' => $produits,
                'filtres' => $filtres,
                'repartition' => $repartition,
                'filtresActifs' => $filtresActifs,
            ])->render();

            return response()->json([
                'html' => $html,
                'total' => $produits->total(),
                'page' => $produits->currentPage(),
                'derniere_page' => $produits->lastPage(),
            ]);
        }

        return view('pages.front.produits.index', [
            'produits' => $produits,
            'categories' => $categories,
            'repartition' => $repartition,
            'filtresActifs' => $filtresActifs,
            'filtres' => $filtres,
        ]);
    }

    // -------------------------------------------------------------------------
    // Méthodes privées
    // -------------------------------------------------------------------------

    /**
     * Valide et normalise les paramètres de filtrage/tri.
     *
     * @return array<string, mixed>
     */
    private function validerFiltres(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categorie' => ['nullable', 'integer', 'exists:categories,id'],
            'famille' => ['nullable', Rule::in(array_keys(config('nutritrace.options.categorie_types')))],
            'score' => ['nullable', Rule::in(array_keys(config('nutritrace.options.scores')))],
            // « label » : ancien paramètre conservé pour rétrocompatibilité
            'label' => ['nullable', Rule::in(array_keys(config('nutritrace.options.certification_types')))],
            // « labels[] » : multi-sélection (nouveau)
            'labels' => ['nullable', 'array'],
            'labels.*' => ['nullable', Rule::in(array_keys(config('nutritrace.options.certification_types')))],
            'origine' => ['nullable', 'string', 'max:120'],
            'certifies' => ['nullable', 'boolean'],
            'tri' => ['nullable', Rule::in(self::TRIS)],
            // Ancien paramètre page géré automatiquement par Laravel
        ]);
    }

    /**
     * Construit la requête Eloquent à partir des filtres validés.
     *
     * @param  array<string, mixed>  $filtres
     */
    private function construireRequete(array $filtres): Builder
    {
        // Fusion label (ancien) + labels[] (nouveau) en une seule liste
        $labelsActifs = collect($filtres['labels'] ?? [])
            ->merge(! empty($filtres['label'] ?? null) ? [$filtres['label']] : [])
            ->filter()
            ->unique()
            ->values();

        $query = Produit::query()
            ->with([
                'categorie:id,nom,type',
                'certifications' => fn ($q) => $q->valides()->with('organisme:id,nom'),
            ])
            ->avecScore();

        // Recherche plein-texte : nom, code-barres, origine, description, composition, catégorie
        $query->when($filtres['q'] ?? null, function ($q, $terme) {
            $q->where(function ($sub) use ($terme) {
                $sub->where('nom', 'like', "%{$terme}%")
                    ->orWhere('code_barres', 'like', "%{$terme}%")
                    ->orWhere('origine', 'like', "%{$terme}%")
                    ->orWhere('description', 'like', "%{$terme}%")
                    ->orWhere('composition', 'like', "%{$terme}%")
                    ->orWhereHas('categorie', fn ($c) => $c->where('nom', 'like', "%{$terme}%"));
            });
        });

        // Filtre catégorie
        $query->when($filtres['categorie'] ?? null, fn ($q, $cat) => $q->where('categorie_id', $cat));

        // Filtre famille (type de catégorie)
        $query->when($filtres['famille'] ?? null, fn ($q, $famille) => $q->whereHas(
            'categorie', fn ($c) => $c->where('type', $famille)
        ));

        // Filtre éco-score exact
        $query->when($filtres['score'] ?? null, fn ($q, $score) => $q->score($score));

        // Filtre labels multiples (certifications valides)
        if ($labelsActifs->isNotEmpty()) {
            foreach ($labelsActifs as $label) {
                $query->whereHas(
                    'certifications',
                    fn ($c) => $c->valides()->where('type', $label)
                );
            }
        }

        // Filtre origine (recherche partielle)
        $query->when($filtres['origine'] ?? null, fn ($q, $origine) => $q->where(
            'origine', 'like', "%{$origine}%"
        ));

        // Filtre « certifiés uniquement »
        $query->when($filtres['certifies'] ?? null, fn ($q) => $q->whereHas(
            'certifications', fn ($c) => $c->valides()
        ));

        // Tri
        match ($filtres['tri'] ?? 'recents') {
            'eco_score' => $query->triEcoScore(),
            'labels' => $query->triLabels(),
            'nom' => $query->orderBy('nom'),
            default => $query->latest(), // 'recents'
        };

        return $query;
    }

    /**
     * Construit la liste des filtres actifs avec leur libellé et l'URL pour les retirer.
     *
     * @param  array<string, mixed>  $filtres
     * @param  Collection  $categories
     * @return array<int, array{cle: string, libelle: string, url: string}>
     */
    private function filtresActifs(array $filtres, $categories): array
    {
        $actifs = [];
        $params = request()->query();

        // Fonction helper : URL courante sans le paramètre donné
        $urlSans = function (string $cle, ?string $valeur = null) use ($params): string {
            $nouveaux = $params;
            if ($valeur !== null && isset($nouveaux[$cle]) && is_array($nouveaux[$cle])) {
                $nouveaux[$cle] = array_values(array_filter(
                    $nouveaux[$cle],
                    fn ($v) => $v !== $valeur
                ));
                if (empty($nouveaux[$cle])) {
                    unset($nouveaux[$cle]);
                }
            } else {
                unset($nouveaux[$cle]);
            }
            unset($nouveaux['page']);

            return route('front.produits.index', $nouveaux);
        };

        if (! empty($filtres['q'])) {
            $actifs[] = ['cle' => 'q', 'libelle' => '« '.e($filtres['q']).' »', 'url' => $urlSans('q')];
        }

        if (! empty($filtres['categorie'])) {
            $nom = $categories->firstWhere('id', $filtres['categorie'])?->nom ?? $filtres['categorie'];
            $actifs[] = ['cle' => 'categorie', 'libelle' => 'Catégorie : '.$nom, 'url' => $urlSans('categorie')];
        }

        if (! empty($filtres['famille'])) {
            $lib = config('nutritrace.options.categorie_types.'.$filtres['famille'], $filtres['famille']);
            $actifs[] = ['cle' => 'famille', 'libelle' => 'Famille : '.$lib, 'url' => $urlSans('famille')];
        }

        if (! empty($filtres['score'])) {
            $actifs[] = ['cle' => 'score', 'libelle' => 'Score '.$filtres['score'], 'url' => $urlSans('score')];
        }

        if (! empty($filtres['label'])) {
            $lib = config('nutritrace.options.certification_types.'.$filtres['label'], $filtres['label']);
            $actifs[] = ['cle' => 'label', 'libelle' => $lib, 'url' => $urlSans('label')];
        }

        foreach ($filtres['labels'] ?? [] as $label) {
            $lib = config('nutritrace.options.certification_types.'.$label, $label);
            $actifs[] = ['cle' => 'labels', 'valeur' => $label, 'libelle' => $lib, 'url' => $urlSans('labels', $label)];
        }

        if (! empty($filtres['origine'])) {
            $actifs[] = ['cle' => 'origine', 'libelle' => 'Origine : '.e($filtres['origine']), 'url' => $urlSans('origine')];
        }

        if (! empty($filtres['certifies'])) {
            $actifs[] = ['cle' => 'certifies', 'libelle' => 'Certifiés uniquement', 'url' => $urlSans('certifies')];
        }

        if (! empty($filtres['tri']) && $filtres['tri'] !== 'recents') {
            $libTri = [
                'eco_score' => 'Tri : Meilleur éco-score',
                'labels' => 'Tri : Plus de labels',
                'nom' => 'Tri : Nom A→Z',
            ];
            $actifs[] = ['cle' => 'tri', 'libelle' => $libTri[$filtres['tri']] ?? '', 'url' => $urlSans('tri')];
        }

        return $actifs;
    }
}
