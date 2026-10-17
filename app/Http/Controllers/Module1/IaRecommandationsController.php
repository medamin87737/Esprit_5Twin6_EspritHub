<?php

declare(strict_types=1);

namespace App\Http\Controllers\Module1;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Services\Module1\OpenRouterEmbedding;
use App\Services\Module1\RepliLocalEmbedding;
use Illuminate\Http\JsonResponse;

/**
 * Module 1 — Route GET /produits/{produit}/recommandations (Ghada).
 *
 * Retourne JSON { html, source, avertissement }.
 * Chargé en différé sur la fiche produit via IntersectionObserver.
 *
 * throttle:60,1 déclaré dans les routes.
 */
class IaRecommandationsController extends Controller
{
    private const NB_RESULTATS = 4;

    public function index(Produit $produit): JsonResponse
    {
        $co2Produit = $produit->co2Moyen();

        // Candidats : tous les produits sauf celui-ci
        $candidats = Produit::query()
            ->where('id', '!=', $produit->id)
            ->avecScore()
            ->with(['categorie:id,nom,type'])
            ->get();

        // Filtre CO₂ strictement plus faible (si le produit a un score)
        // Si le produit n'a pas de score : on prend les scores A ou B
        if ($co2Produit !== null) {
            $candidats = $candidats->filter(fn (Produit $p) => $p->co2Moyen() !== null && $p->co2Moyen() < $co2Produit);
        } else {
            $candidats = $candidats->filter(fn (Produit $p) => in_array($p->scoreGlobal(), ['A', 'B'], true));
        }

        if ($candidats->isEmpty()) {
            return response()->json([
                'html' => '<p class="text-muted small">Aucune alternative plus écologique trouvée pour ce produit.</p>',
                'source' => 'aucun',
                'avertissement' => null,
            ]);
        }

        $service = new OpenRouterEmbedding;
        $estRepli = $service->estRepli();
        $avertissement = null;

        // Récupère ou calcule les embeddings
        $produit->load('categorie:id,nom,type');

        // Embedding du produit courant
        $embedProd = $produit->embedding;
        if (! $produit->embeddingEstAJour()) {
            $vecteurs = $service->embeddings(array_merge([$produit->texteEmbedding()], $candidats->map(fn ($p) => $p->texteEmbedding())->all()));
            $embedProd = $vecteurs[0] ?? [];
            $embedCandidats = $candidats->values()->mapWithKeys(fn ($p, $i) => [$p->id => $vecteurs[$i + 1] ?? []]);
            if ($estRepli || $service->fallbackUsed) {
                $avertissement = 'Recommandations générées par similarité lexicale (IA indisponible).';
            }
        } else {
            $embedCandidats = $candidats->mapWithKeys(fn ($p) => [$p->id => $p->embedding ?? []]);
        }

        // Source effective
        $source = ($estRepli || $service->fallbackUsed) ? 'repli_local' : 'openrouter';

        // Classement : 0.7 × similarité cosinus + 0.3 × gain CO₂
        $scores = $candidats->map(function (Produit $c) use ($embedProd, $embedCandidats, $co2Produit, $produit) {
            $embedCand = $embedCandidats[$c->id] ?? [];
            $similarite = RepliLocalEmbedding::cosinus($embedProd, $embedCand);

            $co2Cand = $c->co2Moyen() ?? 0;
            $co2Ref = $co2Produit ?? 7; // si pas de score, on prend le seuil E
            $gainCo2 = $co2Ref - $co2Cand;
            $gainNormalise = $co2Ref > 0 ? max(0, $gainCo2 / $co2Ref) : 0;

            // Bonus +0.1 si même type de catégorie
            $bonus = ($produit->categorie && $c->categorie && $produit->categorie->type === $c->categorie->type) ? 0.1 : 0;

            $score = 0.7 * $similarite + 0.3 * $gainNormalise + $bonus;

            return [
                'produit' => $c,
                'score' => $score,
                'similarite' => $similarite,
                'gain_co2' => $gainCo2,
            ];
        })
            ->sortByDesc('score')
            ->take(self::NB_RESULTATS)
            ->values();

        $html = view('pages.front.produits._recommandations', [
            'recommandations' => $scores,
        ])->render();

        return response()->json([
            'html' => $html,
            'source' => $source,
            'avertissement' => $avertissement,
        ]);
    }
}
