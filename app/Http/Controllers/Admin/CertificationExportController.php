<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Module 5 — Export certifications :
 *   GET /admin/certifications/{certification}/pdf → télécharge le certificat PDF
 *   GET /admin/certifications/{certification}/qr  → affiche le QR code (API externe)
 */
class CertificationExportController extends Controller
{
    public function pdf(Certification $certification): Response
    {
        $certification->load(['organisme', 'produit.categorie', 'produit.proprietaire']);

        $pdf = Pdf::loadView('pages.admin.certifications.pdf', [
            'certification' => $certification,
        ])->setPaper('a4', 'portrait');

        $nomFichier = 'certificat-'.$certification->numero.'.pdf';

        return $pdf->download($nomFichier);
    }

    public function qr(Certification $certification): \Illuminate\Http\JsonResponse
    {
        $url = route('front.certifications.index', ['numero' => $certification->numero]);

        // GoQR.me — API publique gratuite, sans clé
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?'
            .http_build_query([
                'data'  => $url,
                'size'  => '200x200',
                'color' => '1a3a2a',  // vert NutriTrace
                'bgcolor' => 'ffffff',
                'ecc'   => 'H',
                'format' => 'png',
            ]);

        return response()->json([
            'ok'     => true,
            'qr_url' => $qrUrl,
            'verify_url' => $url,
        ]);
    }
}
