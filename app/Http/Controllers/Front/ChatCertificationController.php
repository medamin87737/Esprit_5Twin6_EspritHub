<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Certifications\CertificationIaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Module 5 IA — Chatbot public "ce label est-il valide ?"
 */
class ChatCertificationController extends Controller
{
    public function __construct(private CertificationIaService $ia) {}

    public function __invoke(Request $request): View
    {
        return view('pages.front.certifications.chat', [
            'numero' => $request->query('numero', ''),
        ]);
    }

    public function repondre(Request $request): JsonResponse
    {
        $request->validate([
            'message'   => ['required', 'string', 'max:500'],
            'historique' => ['nullable', 'array', 'max:12'],
            'numero'    => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $resultat = $this->ia->chat(
                $request->input('message'),
                $request->input('historique', []),
                $request->input('numero')
            );

            return response()->json(['ok' => true] + $resultat);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Erreur IA. Réessayez.'], 500);
        }
    }
}
