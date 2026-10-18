<?php

namespace App\Http\Controllers\Consommateur;

use App\Http\Controllers\Controller;
use App\Services\Analyses\ChatQualiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ChatQualiteController extends Controller
{
    public function __construct(private ChatQualiteService $chat) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:800'],
            'produit_id' => ['nullable', 'integer', 'exists:produits,id'],
            'historique' => ['nullable', 'array', 'max:8'],
            'historique.*.role' => ['required_with:historique', 'in:user,assistant'],
            'historique.*.content' => ['required_with:historique', 'string', 'max:800'],
        ], [
            'message.required' => 'Écrivez votre question.',
        ]);

        try {
            $resultat = $this->chat->repondre(
                $data['message'],
                $data['historique'] ?? [],
                isset($data['produit_id']) ? (int) $data['produit_id'] : null,
            );

            return response()->json($resultat);
        } catch (Throwable $e) {
            return response()->json([
                'reponse' => 'Je n\'ai pas pu traiter votre question pour le moment. Réessayez dans un instant.',
                'produits' => [],
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
