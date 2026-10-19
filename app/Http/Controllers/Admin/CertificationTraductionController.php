<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Services\Certifications\TraductionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Module 5 — Traduction de fiche certification
 * POST /admin/certifications/{certification}/traduire
 */
class CertificationTraductionController extends Controller
{
    public function __construct(private TraductionService $traducteur) {}

    public function __invoke(Request $request, Certification $certification): JsonResponse
    {
        $request->validate([
            'langue' => ['required', 'string', 'in:fr,en,ar'],
        ]);

        $certification->load(['organisme', 'produit.categorie']);

        try {
            $traduit = $this->traducteur->traduire($certification, $request->input('langue'));

            return response()->json([
                'ok'     => true,
                'langue' => $request->input('langue'),
                'champs' => $traduit,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Erreur de traduction.'], 500);
        }
    }
}
