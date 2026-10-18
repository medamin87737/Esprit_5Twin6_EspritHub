<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmpreinteCarbone;
use App\Services\DecarbonationIaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmpreinteIaController extends Controller
{
    /**
     * Déclenche un audit de décarbonation IA pour une empreinte donnée.
     */
    public function auditer(Request $request, EmpreinteCarbone $empreinte, DecarbonationIaService $service): JsonResponse|RedirectResponse
    {
        $force = (bool) $request->boolean('force', false);
        $cacheKey = "empreinte_audit_ia_{$empreinte->id}";

        if ($force) {
            Cache::forget($cacheKey);
        }

        $audit = Cache::remember($cacheKey, now()->addDays(7), function () use ($service, $empreinte) {
            return $service->analyser($empreinte);
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'audit' => $audit,
            ]);
        }

        return back()->with('audit_ia', $audit)->with('status', 'Audit de décarbonation IA généré avec succès.');
    }

    /**
     * Réinitialise l'audit en cache pour forcer un nouveau calcul IA.
     */
    public function reinitialiser(EmpreinteCarbone $empreinte): RedirectResponse
    {
        Cache::forget("empreinte_audit_ia_{$empreinte->id}");

        return back()->with('status', 'Audit IA réinitialisé.');
    }
}
