<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laboratoire;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV des laboratoires filtrés (Module 2 — Amin).
 *
 * Applique exactement les mêmes filtres que la liste des laboratoires
 * (recherche `q` + `ville`) : ce qui est téléchargé = ce qui est filtré à l'écran.
 */
class LaboratoireExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $nom = 'laboratoires-nutritrace-'.now()->format('Y-m-d-His').'.csv';

        $query = Laboratoire::query()
            ->withCount(['analyses', 'analyses as non_conformes_count' => fn ($q) => $q->resultat('non_conforme')])
            ->search($request->string('q')->trim()->value())
            ->when($request->filled('ville'), fn ($q) => $q->where('ville', $request->input('ville')))
            ->orderBy('nom');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Nom', 'Ville', 'Pays', 'Accreditation', 'E-mail', 'Telephone',
                'Latitude', 'Longitude', 'Analyses', 'Non conformes', 'Ajoute le',
            ], ';');

            $query->chunk(100, function ($laboratoires) use ($out) {
                foreach ($laboratoires as $laboratoire) {
                    fputcsv($out, [
                        $laboratoire->nom,
                        $laboratoire->ville,
                        $laboratoire->pays,
                        $laboratoire->accreditation,
                        $laboratoire->email,
                        $laboratoire->telephone,
                        $laboratoire->latitude,
                        $laboratoire->longitude,
                        $laboratoire->analyses_count,
                        $laboratoire->non_conformes_count,
                        $laboratoire->created_at?->format('Y-m-d'),
                    ], ';');
                }
            });

            fclose($out);
        }, $nom, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
