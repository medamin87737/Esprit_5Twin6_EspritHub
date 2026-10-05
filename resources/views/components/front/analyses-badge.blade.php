@props(['synthese' => null])

@php
    [$classe, $icone, $libelle] = match ($synthese) {
        'conforme' => ['status-valid', 'bi-shield-check', 'Analysé : conforme'],
        'non_conforme' => ['status-expired', 'bi-exclamation-octagon', 'Non-conformité détectée'],
        'en_attente' => ['status-pending', 'bi-hourglass-split', 'Analyse en cours'],
        default => [null, 'bi-dash-circle', 'Non analysé'],
    };
@endphp

@if ($classe)
    <span {{ $attributes->class(['status-pill', $classe]) }}><i class="bi {{ $icone }} me-1" aria-hidden="true"></i>{{ $libelle }}</span>
@else
    <span {{ $attributes->class(['small text-muted']) }}><i class="bi {{ $icone }} me-1" aria-hidden="true"></i>{{ $libelle }}</span>
@endif
