<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Téléchargement du rapport d'une analyse :
 * - le fichier joint par le laboratoire s'il existe ;
 * - sinon un rapport qualité NutriTrace généré à la volée (document HTML imprimable).
 */
class RapportAnalyseController extends Controller
{
    public function __invoke(Analyse $analyse): Response|StreamedResponse
    {
        $disque = Storage::disk(Analyse::DISQUE);

        if ($analyse->rapport && $disque->exists($analyse->rapport)) {
            $ext = pathinfo((string) $analyse->rapport, PATHINFO_EXTENSION) ?: 'txt';

            return $disque->download($analyse->rapport, "Rapport-{$analyse->numero}.{$ext}");
        }

        $analyse->loadMissing(['lot.produit', 'laboratoire', 'declarant:id,name']);

        $html = view('pages.front.consommateur.rapport-analyse', ['analyse' => $analyse])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Rapport-'.$analyse->numero.'.html"',
        ]);
    }
}
