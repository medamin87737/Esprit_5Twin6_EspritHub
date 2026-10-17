<?php

declare(strict_types=1);

namespace App\Http\Controllers\Module1;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use App\Services\Module1\Contrats\OcrInterface;
use App\Services\Module1\OpenRouterCategoriseur;
use App\Services\Module1\OpenRouterOcr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

/**
 * Module 1 — Route POST /ia/produits/ocr (Ghada).
 *
 * Auth + Gate('create', Produit) + throttle déclarés dans les routes.
 * Sans clé API : HTTP 503 avec message clair, pas de repli.
 */
class IaOcrController extends Controller
{
    public function lire(Request $request): JsonResponse
    {
        Gate::authorize('create', Produit::class);

        $data = $request->validate([
            'image' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4096)],
        ]);

        /** @var UploadedFile $image */
        $image = $data['image'];

        /** @var OcrInterface $service */
        $service = new OpenRouterOcr;

        try {
            $resultat = $service->lire($image);

            // Enchaîne avec IA1 : propose la catégorie à partir du nom lu
            if (! empty($resultat['nom'])) {
                try {
                    $categoriseur = new OpenRouterCategoriseur;
                    $categories = Categorie::orderBy('nom')
                        ->get(['id', 'nom', 'type'])
                        ->map(fn ($c) => ['id' => $c->id, 'nom' => $c->nom, 'type' => $c->type])
                        ->all();

                    $suggestion = $categoriseur->categoriser(
                        $resultat['nom'],
                        $resultat['description'] ?? '',
                        $resultat['composition'] ?? '',
                        $categories
                    );

                    $resultat['categorie_id'] = $suggestion['categorie_id'] ?? null;
                    $resultat['categorie_nom'] = $suggestion['categorie_nom'] ?? null;
                    $resultat['type'] = $suggestion['type'] ?? null;
                } catch (\Throwable $e) {
                    // Ne bloque pas l'OCR si la catégorisation échoue
                }
            }

            return response()->json($resultat);

        } catch (\RuntimeException $e) {
            // Sans clé : 503
            return response()->json(['message' => $e->getMessage()], 503);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur lors de la lecture de l\'étiquette : '.$e->getMessage()], 500);
        }
    }
}
