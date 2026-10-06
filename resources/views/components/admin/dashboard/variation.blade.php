@props(['courant', 'precedent', 'inverse' => false])

@php
    $variation = \App\Support\TableauDeBord::variation($courant, $precedent);
    $hausse = $variation !== null && $variation > 0;
    $favorable = $variation !== null && $variation != 0 && ($hausse xor $inverse);
@endphp

<span {{ $attributes->class(['nt-delta', 'is-up' => $favorable, 'is-down' => $variation !== null && $variation != 0 && ! $favorable]) }}>
    @if ($variation === null)
        @if (($courant ?? 0) > 0 && ($precedent ?? 0) == 0)
            <span class="nt-delta-value"><i class="bi bi-stars" aria-hidden="true"></i> Nouveau</span>
            <span class="nt-delta-caption">aucune donnée la période précédente</span>
        @else
            <span class="nt-delta-value">—</span>
            <span class="nt-delta-caption">pas de comparaison possible</span>
        @endif
    @elseif ($variation == 0)
        <span class="nt-delta-value"><i class="bi bi-dash" aria-hidden="true"></i> Stable</span>
        <span class="nt-delta-caption">vs période précédente</span>
    @else
        <span class="nt-delta-value">
            <i class="bi {{ $hausse ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}" aria-hidden="true"></i>
            <span class="sr-only">{{ $hausse ? 'En hausse de' : 'En baisse de' }}&nbsp;</span>
            {{ \App\Support\TableauDeBord::nombre(abs($variation), 1) }} %
        </span>
        <span class="nt-delta-caption">vs période précédente</span>
    @endif
</span>
