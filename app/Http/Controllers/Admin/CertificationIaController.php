<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Services\Certifications\CertificationIaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Module 5 IA — actions admin :
 *   POST /admin/certifications/{certification}/ia/upload   → upload du document
 *   POST /admin/certifications/{certification}/ia/verifier → vérification du PDF
 *   POST /admin/certifications/{certification}/ia/incoherences → détection d'anomalies
 */
class CertificationIaController extends Controller
{
    public function __construct(private CertificationIaService $ia) {}

    public function upload(Request $request, Certification $certification): RedirectResponse
    {
        $request->validate([
            'document_pdf' => ['required', 'file', 'mimes:txt,docx', 'max:5120'],
        ], [
            'document_pdf.mimes' => 'Formats acceptés : .txt, .docx (max 5 Mo).',
        ]);

        // Supprimer l'ancien fichier si présent
        if ($certification->document_pdf) {
            Storage::disk(Certification::DISQUE)->delete($certification->document_pdf);
        }

        $chemin = $request->file('document_pdf')->store('certifications/documents', Certification::DISQUE);
        $certification->update(['document_pdf' => $chemin]);

        return back()->with('success', 'Document joint. Vous pouvez maintenant lancer la vérification IA.');
    }

    public function verifier(Certification $certification): JsonResponse
    {
        try {
            $resultat = $this->ia->verifierDocument($certification);

            $resume = $resultat['resume'];
            $certification->forceFill([
                'ia_verification' => $resume,
                'ia_verifie_at'   => now(),
            ])->save();

            return response()->json([
                'ok'        => true,
                'valide'    => $resultat['valide'],
                'confiance' => $resultat['confiance'],
                'resume'    => $resume,
                'champs'    => $resultat['champs'],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Erreur IA inattendue.'], 500);
        }
    }

    public function incoherences(Certification $certification): JsonResponse
    {
        try {
            $resultat = $this->ia->detecterIncoherences($certification);

            $certification->forceFill([
                'ia_incoherences' => implode("\n", $resultat['incoherences']),
                'ia_verifie_at'   => now(),
            ])->save();

            return response()->json([
                'ok'            => true,
                'incoherences'  => $resultat['incoherences'],
                'niveau'        => $resultat['niveau'],
                'justification' => $resultat['justification'],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Erreur IA inattendue.'], 500);
        }
    }
}
