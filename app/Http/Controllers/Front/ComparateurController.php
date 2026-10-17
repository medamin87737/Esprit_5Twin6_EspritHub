<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Module 1 — Comparateur de fiches produits (Ghada).
 *
 * Compare les fiches produits (éco-score, CO₂, labels, lots, % conformité…).
 * DISTINCT du comparateur de Sahar (/comparer) qui compare les indicateurs
 * environnementaux quantitatifs (eau, énergie, transport, emballage) par lot.
 *
 * Limite : visiteur = config('nutritrace.comparaison.visiteur')
 *          connecté = config('nutritrace.comparaison.connecte')
 *          Gate 'comparer-sans-limite' pour les utilisateurs connectés.
 *
 * Sélection persistée en session sous la clé 'm1_comparaison'.
 */
class ComparateurController extends Controller
{
    /** Clé de session pour la sélection. */
    private const SESSION_KEY = 'm1_comparaison';

    // ─────────────────────────────────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Page de comparaison des fiches.
     * Supporte aussi ?produits[]=1&produits[]=2 comme lien partageable.
     */
    public function index(Request $request): View
    {
        $max = $this->limiteMax();

        // Lien partageable : les ids viennent de l'URL
        $idsUrl = collect((array) $request->query('produits', []))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($idsUrl->isNotEmpty()) {
            // On ne surécrit pas la session si on arrive via un lien partagé
            $ids = $idsUrl->take($max);
        } else {
            $ids = collect($request->session()->get(self::SESSION_KEY, []));
        }

        $produits = Produit::query()
            ->with([
                'categorie:id,nom',
                'certifications' => fn ($q) => $q->valides(),
                'lots.analyses:id,lot_id,resultat',
            ])
            ->withCount('lots')
            ->avecScore()
            ->whereIn('id', $ids->take($max)->all())
            ->get()
            ->sortBy(fn (Produit $p) => $ids->search($p->id))
            ->values();

        $donnees = $produits->mapWithKeys(fn (Produit $p) => [$p->id => $this->calculerDonnees($p)]);

        // URL partageable courante
        $urlPartage = $ids->isNotEmpty()
            ? route('front.produits.comparer', ['produits' => $ids->all()])
            : null;

        return view('pages.front.produits.comparer', [
            'produits' => $produits,
            'donnees' => $donnees,
            'max' => $max,
            'selection' => $request->session()->get(self::SESSION_KEY, []),
            'urlPartage' => $urlPartage,
            'lienSahar' => route('front.comparaison'),
        ]);
    }

    /**
     * Ajouter un produit à la sélection.
     * JSON si requête AJAX, redirect back() sinon.
     */
    public function ajouter(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['produit_id' => ['required', 'integer', 'exists:produits,id']]);

        $max = $this->limiteMax();
        $selection = $request->session()->get(self::SESSION_KEY, []);
        $id = (int) $request->input('produit_id');

        if (in_array($id, $selection, true)) {
            return $this->repondre($request, $selection, $max, $id, true, null);
        }

        if (count($selection) >= $max) {
            $message = "Vous ne pouvez comparer que {$max} produits. Retirez-en un avant d'en ajouter un autre.";

            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : back()->with('error', $message);
        }

        $selection[] = $id;
        $request->session()->put(self::SESSION_KEY, $selection);

        return $this->repondre($request, $selection, $max, $id, true, null);
    }

    /**
     * Retirer un produit de la sélection.
     * JSON si requête AJAX, redirect back() sinon.
     */
    public function retirer(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $selection = $request->session()->get(self::SESSION_KEY, []);
        $selection = array_values(array_filter($selection, fn ($pid) => $pid !== $id));
        $request->session()->put(self::SESSION_KEY, $selection);

        return $this->repondre($request, $selection, $this->limiteMax(), $id, false, null);
    }

    /**
     * Vider toute la sélection.
     */
    public function vider(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        if ($request->expectsJson()) {
            return response()->json(['nb' => 0, 'max' => $this->limiteMax()]);
        }

        return back()->with('status', 'La sélection a été vidée.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privés
    // ─────────────────────────────────────────────────────────────────────────

    /** Limite selon le droit Gate ou la config. */
    private function limiteMax(): int
    {
        return Gate::allows('comparer-sans-limite')
            ? (int) config('nutritrace.comparaison.connecte')
            : (int) config('nutritrace.comparaison.visiteur');
    }

    /**
     * Calcule les données comparées pour un produit.
     *
     * @return array<string, mixed>
     */
    private function calculerDonnees(Produit $produit): array
    {
        $lots = $produit->lots;
        $nbLots = $produit->lots_count ?? $lots->count();
        $lotsAnalyses = $lots->filter(fn ($l) => $l->analyses->isNotEmpty());

        // % lots dont toutes les analyses sont conformes
        $lotsConformes = $lotsAnalyses->filter(
            fn ($l) => $l->analyses->every(fn ($a) => $a->resultat === 'conforme')
        );

        $pctConformes = $lotsAnalyses->isNotEmpty()
            ? round($lotsConformes->count() / $lotsAnalyses->count() * 100)
            : null;

        // Nombre d'ingrédients (séparés par virgule dans la composition)
        $nbIngredients = null;
        if ($produit->composition) {
            $nbIngredients = count(array_filter(
                array_map('trim', explode(',', $produit->composition))
            ));
        }

        return [
            'co2' => $produit->co2Moyen(),
            'score' => $produit->scoreGlobal(),
            'certifications' => $produit->certifications->count(),
            'nb_lots' => $nbLots,
            'pct_conformes' => $pctConformes,
            'nb_ingredients' => $nbIngredients,
            'categorie' => $produit->categorie?->nom,
            'origine' => $produit->origine,
        ];
    }

    /**
     * Retourne une réponse JSON ou une redirection selon le type de requête.
     *
     * @param  int[]  $selection
     */
    private function repondre(
        Request $request,
        array $selection,
        int $max,
        int $id,
        bool $selectionne,
        ?string $message
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'nb' => count($selection),
                'max' => $max,
                'selectionne' => $selectionne,
                'id' => $id,
            ]);
        }

        $flash = $message ?? ($selectionne
            ? 'Produit ajouté à la comparaison.'
            : 'Produit retiré de la comparaison.');

        return back()->with('status', $flash);
    }
}
