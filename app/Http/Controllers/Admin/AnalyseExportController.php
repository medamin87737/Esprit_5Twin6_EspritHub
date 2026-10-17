<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV des analyses filtrées (fonctionnalité avancée module 2).
 */
class AnalyseExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $nom = 'analyses-nutritrace-'.now()->format('Y-m-d-His').'.csv';

        $query = Analyse::query()
            ->with(['lot:id,numero_lot,produit_id', 'lot.produit:id,nom', 'laboratoire:id,nom'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('numero', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('lot', fn ($l) => $l->where('numero_lot', 'like', '%'.$request->input('q').'%'))
                ->orWhereHas('lot.produit', fn ($p) => $p->where('nom', 'like', '%'.$request->input('q').'%'))))
            ->when($request->filled('resultat'), fn ($q) => $q->resultat($request->input('resultat')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('laboratoire'), fn ($q) => $q->where('laboratoire_id', $request->input('laboratoire')))
            ->when($request->filled('lot'), fn ($q) => $q->where('lot_id', $request->input('lot')))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('date_prelevement', '>=', $request->input('du')))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('date_prelevement', '<=', $request->input('au')))
            ->latest('date_prelevement');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Numero', 'Type', 'Resultat', 'Lot', 'Produit', 'Laboratoire',
                'Date prelevement', 'Date resultat', 'Commentaire', 'Rapport PDF', 'Resume IA',
            ], ';');

            $query->chunk(100, function ($analyses) use ($out) {
                foreach ($analyses as $analyse) {
                    fputcsv($out, [
                        $analyse->numero,
                        $analyse->typeLabel(),
                        $analyse->resultatLabel(),
                        $analyse->lot?->numero_lot,
                        $analyse->lot?->produit?->nom,
                        $analyse->laboratoire?->nom,
                        $analyse->date_prelevement?->format('Y-m-d'),
                        $analyse->date_resultat?->format('Y-m-d'),
                        $analyse->commentaire,
                        $analyse->rapport ? 'oui' : 'non',
                        $analyse->resume_ia,
                    ], ';');
                }
            });

            fclose($out);
        }, $nom, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
