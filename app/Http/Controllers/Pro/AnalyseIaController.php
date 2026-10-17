<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use App\Models\Lot;
use App\Services\Analyses\AnalyseIaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class AnalyseIaController extends Controller
{
    public function __construct(private AnalyseIaService $ia) {}

    public function prefill(Request $request, Lot $lot): JsonResponse
    {
        $this->authorize('ajouterAnalyse', $lot);

        $request->validate([
            'rapport' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        try {
            $this->assurerCle();
            $extrait = $this->ia->extraireDepuisPdf($request->file('rapport'));

            return response()->json([
                'ok' => true,
                'message' => 'Champs proposés par l\'IA — vérifiez avant d\'enregistrer.',
                'data' => $extrait,
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function extraire(Analyse $analyse): RedirectResponse
    {
        $this->authorize('update', $analyse);

        if (! $analyse->rapport) {
            return back()->with('error', 'Cette analyse n\'a pas encore de rapport PDF.');
        }

        try {
            $this->assurerCle();
            $extrait = $this->ia->extraireDepuisPdf($analyse->rapport);

            $analyse->fill(array_filter([
                'type' => $extrait['type'],
                'resultat' => $extrait['resultat'],
                'date_prelevement' => $extrait['date_prelevement'],
                'date_resultat' => $extrait['date_resultat'],
                'commentaire' => $extrait['commentaire'],
            ], fn ($v) => $v !== null));

            if ($extrait['resume_court']) {
                $analyse->resume_ia = $extrait['resume_court'];
                $analyse->ia_genere_at = now();
            }

            $analyse->save();

            return back()->with('status', 'L\'IA a extrait les informations du rapport.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resumer(Analyse $analyse): RedirectResponse
    {
        $this->authorize('update', $analyse);

        try {
            $this->assurerCle();
            $this->ia->resumer($analyse);

            return redirect()->route('pro.lots.show', $analyse->lot)
                ->with('status', 'Résumé IA généré pour '.$analyse->numero.'.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function risque(Lot $lot): RedirectResponse
    {
        $this->authorize('view', $lot);

        try {
            $risque = $this->ia->evaluerRisque($lot);

            return back()->with('risque_ia', $risque)->with('status', $risque['libelle'].' ('.$risque['score'].'/100).');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function assurerCle(): void
    {
        if (! $this->ia->disponible()) {
            throw new RuntimeException('Ajoutez OPENROUTER_API_KEY dans le fichier .env.');
        }
    }
}
