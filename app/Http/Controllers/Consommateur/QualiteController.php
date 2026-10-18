<?php

namespace App\Http\Controllers\Consommateur;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use App\Models\Lot;
use App\Services\Analyses\AnalyseIaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Hub Qualité consommateur : consulter un lot et résumer analyses / rapport via IA.
 */
class QualiteController extends Controller
{
    public function __construct(private AnalyseIaService $ia) {}

    public function index(Request $request): View
    {
        $lot = null;
        $numero = strtoupper(trim((string) $request->query('numero', '')));

        if ($numero !== '') {
            $lot = Lot::query()
                ->with([
                    'produit:id,nom,categorie_id',
                    'produit.categorie:id,nom',
                    'analyses' => fn ($q) => $q->with('laboratoire:id,nom')->latest(),
                ])
                ->where('numero_lot', $numero)
                ->first();
        }

        return view('pages.front.consommateur.qualite', [
            'numero' => $numero,
            'lot' => $lot,
            'introuvable' => $numero !== '' && $lot === null,
        ]);
    }

    public function resumerAnalyse(Request $request, Analyse $analyse): RedirectResponse
    {
        try {
            $resume = $this->ia->resumer($analyse);

            return back()->with('status', 'Résumé IA généré.')
                ->with('resume_ia_affiche', $resume)
                ->with('analyse_resumee_id', $analyse->id);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resumerFichier(Request $request): RedirectResponse|View
    {
        $request->validate([
            'rapport' => ['required', 'file', 'mimes:txt,docx,text', 'max:5120'],
            'numero_lot' => ['nullable', 'string', 'max:30'],
        ], [
            'rapport.required' => 'Choisissez un fichier TXT ou DOCX.',
            'rapport.mimes' => 'Formats acceptés : TXT ou DOCX.',
        ]);

        try {
            $data = $this->ia->extraireDepuisPdf($request->file('rapport'));

            return view('pages.front.consommateur.qualite-resume', [
                'data' => $data,
                'nomFichier' => $request->file('rapport')->getClientOriginalName(),
                'numero' => strtoupper(trim((string) $request->input('numero_lot', ''))),
            ]);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
