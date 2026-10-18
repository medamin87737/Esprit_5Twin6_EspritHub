<?php

namespace App\Http\Controllers\Admin;

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

    /**
     * Pré-remplit le formulaire à partir d'un rapport TXT/DOCX (JSON pour le JS).
     */
    public function prefill(Request $request): JsonResponse
    {
        $request->validate([
            'rapport' => ['required', 'file', 'mimes:txt,docx,text', 'max:5120'],
        ], [
            'rapport.required' => 'Choisissez d\'abord un rapport (TXT ou DOCX).',
            'rapport.mimes' => 'Formats acceptés : TXT ou DOCX.',
        ]);

        try {
            $this->assurerCle();
            $extrait = $this->ia->extraireDepuisPdf($request->file('rapport'));

            return response()->json([
                'ok' => true,
                'message' => 'Champs proposés par l\'IA — vérifiez avant d\'enregistrer.',
                'data' => $extrait,
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8'),
            ], 422, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        }
    }

    public function extraire(Analyse $analyse): RedirectResponse
    {
        if (! $analyse->rapport) {
            return back()->with('error', 'Cette analyse n\'a pas encore de rapport.');
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

            return back()->with('success', 'L\'IA a extrait les informations du rapport. Vérifiez puis enregistrez si besoin.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resumer(Analyse $analyse): RedirectResponse
    {
        try {
            $this->assurerCle();
            $this->ia->resumer($analyse);

            return back()->with('success', 'Résumé IA généré pour '.$analyse->numero.'.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function risque(Lot $lot): RedirectResponse
    {
        try {
            $risque = $this->ia->evaluerRisque($lot);

            return back()->with('risque_ia', $risque)
                ->with('success', $risque['libelle'].' — score '.$risque['score'].'/100.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function assurerCle(): void
    {
        if (! $this->ia->disponible()) {
            throw new RuntimeException('Ajoutez OPENROUTER_API_KEY dans le fichier .env puis videz le cache config.');
        }
    }
}
