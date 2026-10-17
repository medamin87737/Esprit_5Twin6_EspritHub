<?php

namespace App\Services\Analyses;

use App\Models\Lot;
use Illuminate\Support\Collection;

/**
 * Synthèse conformité d'un lot (règles métier, sans IA).
 */
class AnalyseSynthese
{
    /**
     * @return array{
     *     statut: ?string,
     *     libelle: string,
     *     classe: string,
     *     total: int,
     *     conformes: int,
     *     non_conformes: int,
     *     en_attente: int,
     *     en_attente_anciennes: int,
     *     alertes: list<string>
     * }
     */
    public function pourLot(Lot $lot): array
    {
        $lot->loadMissing('analyses');
        /** @var Collection<int, \App\Models\Analyse> $analyses */
        $analyses = $lot->analyses;
        $seuil = (int) config('openrouter.alerte_en_attente_jours', 7);

        $conformes = $analyses->where('resultat', 'conforme')->count();
        $nonConformes = $analyses->where('resultat', 'non_conforme')->count();
        $enAttente = $analyses->where('resultat', 'en_attente');
        $anciennes = $enAttente->filter(
            fn ($a) => ($a->date_prelevement?->diffInDays(today()) ?? 0) >= $seuil
        )->count();

        $statut = $lot->syntheseAnalyses();
        $alertes = [];

        if ($nonConformes > 0) {
            $alertes[] = "{$nonConformes} analyse(s) non conforme(s) — lot à bloquer ou retraiter.";
        }
        if ($anciennes > 0) {
            $alertes[] = "{$anciennes} analyse(s) en attente depuis plus de {$seuil} jours.";
        }
        if ($analyses->isEmpty()) {
            $alertes[] = 'Aucune analyse qualité enregistrée pour ce lot.';
        }

        return [
            'statut' => $statut,
            'libelle' => match ($statut) {
                'non_conforme' => 'Lot non conforme',
                'conforme' => 'Lot conforme',
                'en_attente' => 'Analyses en attente',
                default => 'Pas encore d\'analyse',
            },
            'classe' => match ($statut) {
                'non_conforme' => 'danger',
                'conforme' => 'success',
                'en_attente' => 'warning',
                default => 'muted',
            },
            'total' => $analyses->count(),
            'conformes' => $conformes,
            'non_conformes' => $nonConformes,
            'en_attente' => $enAttente->count(),
            'en_attente_anciennes' => $anciennes,
            'alertes' => $alertes,
        ];
    }
}
