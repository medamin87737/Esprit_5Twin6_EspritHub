<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Periode;
use App\Support\TableauDeBord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $periode = Periode::depuisRequete($request);
        $tableau = new TableauDeBord($periode);

        $tendances = $tableau->tendances();
        $repartition = $tableau->repartitionLots();
        $certifications = $tableau->certifications();
        $environnement = $tableau->environnement();

        return view('pages.admin.dashboard', [
            'periode' => $periode,
            'precedente' => $tableau->precedente(),
            'kpis' => $tableau->kpis(),
            'tendances' => $tendances,
            'repartition' => $repartition,
            'certifications' => $certifications,
            'environnement' => $environnement,
            'activite' => $tableau->activite(10),
            'alertes' => $tableau->alertes(),
            'indice' => $tableau->indice(),
            'graphiques' => [
                'libelles' => $tableau->libelles(),
                'libellesLongs' => $tableau->libelles(true),
                'libellesPrecedents' => $tableau->libelles(true, $tableau->precedente()),
                'tendances' => $tendances,
                'repartition' => $repartition,
                'certifications' => $certifications,
                'co2' => $environnement['serie'],
            ],
        ]);
    }
}
