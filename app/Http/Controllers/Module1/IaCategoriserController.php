<?php

declare(strict_types=1);

namespace App\Http\Controllers\Module1;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use App\Services\Module1\OpenRouterCategoriseur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Module 1 — Route POST /ia/produits/categoriser (Ghada).
 *
 * Auth + Gate('create', Produit) + throttle déclarés dans les routes.
 */
class IaCategoriserController extends Controller
{
    public function categoriser(Request $request): JsonResponse
    {
        Gate::authorize('create', Produit::class);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'composition' => ['nullable', 'string', 'max:2000'],
        ]);

        $categories = Categorie::orderBy('nom')
            ->get(['id', 'nom', 'type'])
            ->map(fn ($c) => ['id' => $c->id, 'nom' => $c->nom, 'type' => $c->type])
            ->all();

        $service = new OpenRouterCategoriseur;
        $resultat = $service->categoriser(
            $data['nom'],
            $data['description'] ?? '',
            $data['composition'] ?? '',
            $categories
        );

        return response()->json($resultat);
    }
}
