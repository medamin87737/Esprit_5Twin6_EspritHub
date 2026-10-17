@php
    $synthese = app(\App\Services\Analyses\AnalyseSynthese::class)->pourLot($lot);
    $risque = session('risque_ia');
@endphp

<div class="card mb-4 nt-synthese-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap: 0.5rem;">
        <h2 class="nt-card-title mb-0">Synthèse conformité</h2>
        <form method="POST" action="{{ route('admin.lots.ia.risque', $lot) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-shield-check mr-1" aria-hidden="true"></i> Score de risque IA
            </button>
        </form>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center mb-3" style="gap: 0.75rem;">
            <span class="nt-badge nt-badge-{{ $synthese['classe'] === 'danger' ? 'danger' : ($synthese['classe'] === 'success' ? '' : ($synthese['classe'] === 'warning' ? 'gold' : 'muted')) }}">
                {{ $synthese['libelle'] }}
            </span>
            <span class="small text-muted">
                {{ $synthese['total'] }} analyse{{ $synthese['total'] > 1 ? 's' : '' }}
                · {{ $synthese['conformes'] }} conforme{{ $synthese['conformes'] > 1 ? 's' : '' }}
                · {{ $synthese['non_conformes'] }} non conforme{{ $synthese['non_conformes'] > 1 ? 's' : '' }}
                · {{ $synthese['en_attente'] }} en attente
            </span>
        </div>

        @foreach ($synthese['alertes'] as $alerte)
            <p class="small mb-1"><i class="bi bi-exclamation-circle mr-1 text-warning" aria-hidden="true"></i>{{ $alerte }}</p>
        @endforeach

        @if ($risque)
            <div class="nt-risque nt-risque-{{ $risque['niveau'] }} mt-3">
                <strong>{{ $risque['libelle'] }}</strong>
                <span class="nt-risque-score">{{ $risque['score'] }}/100</span>
                <p class="mb-0 mt-2">{{ $risque['justification'] }}</p>
            </div>
        @endif
    </div>
</div>
