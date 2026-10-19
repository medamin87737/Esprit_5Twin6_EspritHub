<?php

namespace App\Http\Controllers\Consommateur;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use App\Models\Lot;
use App\Services\Analyses\AnalyseIaService;
use App\Services\Analyses\AnalyseSynthese;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Hub Qualité consommateur : consulter un lot, risque IA et synthèses d'analyses.
 * L'upload de rapports reste réservé aux espaces pro / admin.
 */
class QualiteController extends Controller
{
    public function __construct(
        private AnalyseIaService $ia,
        private AnalyseSynthese $synthese,
    ) {}

    public function index(Request $request): View
    {
        $lot = null;
        $synthese = null;
        $numero = strtoupper(trim((string) $request->query('numero', '')));

        if ($numero !== '') {
            $lot = Lot::query()
                ->with([
                    'produit:id,nom,categorie_id,image,origine,composition,code_barres',
                    'produit.categorie:id,nom',
                    'produit.certifications' => fn ($q) => $q->valides()->with('organisme:id,nom')->latest('date_expiration'),
                    'analyses' => fn ($q) => $q->with('laboratoire:id,nom')->latest(),
                ])
                ->where('numero_lot', $numero)
                ->first();

            if ($lot) {
                $synthese = $this->synthese->pourLot($lot);
            }
        }

        return view('pages.front.consommateur.qualite', [
            'numero' => $numero,
            'lot' => $lot,
            'synthese' => $synthese,
            'introuvable' => $numero !== '' && $lot === null,
        ]);
    }

    public function risqueLot(Request $request, Lot $lot): RedirectResponse
    {
        $data = $request->validate([
            'profil_sante' => ['nullable', 'string', 'max:240'],
        ], [], [
            'profil_sante' => 'profil santé',
        ]);

        try {
            $risque = $this->ia->evaluerRisque($lot, $data['profil_sante'] ?? null);

            return redirect()
                ->route('consommateur.qualite.index', ['numero' => $lot->numero_lot])
                ->with('risque_ia', $risque)
                ->with('risque_ia_popup', true)
                ->with('status', 'Évaluation terminée — consultez le verdict.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resumerAnalyse(Request $request, Analyse $analyse): RedirectResponse
    {
        try {
            $resume = $this->ia->resumer($analyse);

            return back()->with('status', 'Synthèse qualité générée.')
                ->with('resume_ia_affiche', $resume)
                ->with('analyse_resumee_id', $analyse->id);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
