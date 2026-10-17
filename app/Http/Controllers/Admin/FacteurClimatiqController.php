<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClimatiqService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacteurClimatiqController extends Controller
{
    public function index(Request $request, ClimatiqService $climatiq): View
    {
        $facteurs = $climatiq->obtenirFacteurs();
        $mode = $climatiq->mode();
        $estConfigure = $climatiq->estConfigure();

        $estimation = null;
        if ($request->filled('test_type') && $request->filled('test_valeur')) {
            $type = (string) $request->input('test_type');
            $valeur = (float) $request->input('test_valeur');

            $estimation = $climatiq->estimer($type, $valeur);
            $estimation['type'] = $type;
            $estimation['valeur_saisie'] = $valeur;
        }

        return view('pages.admin.facteurs-climatiq.index', [
            'facteurs' => $facteurs,
            'mode' => $mode,
            'estConfigure' => $estConfigure,
            'estimation' => $estimation,
        ]);
    }

    public function synchroniser(ClimatiqService $climatiq): RedirectResponse
    {
        $resultat = $climatiq->synchroniser();

        return redirect()->route('admin.facteurs-climatiq.index')
            ->with('success', "Facteurs d'émission synchronisés avec succès via Climatiq API ({$resultat['date']}). {$resultat['nombre_recalcules']} empreintes carbone ont été instantanément recalculées.");
    }
}
