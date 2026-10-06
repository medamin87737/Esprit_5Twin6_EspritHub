@props(['valeurs' => []])

@php
    $points = collect($valeurs)->map(fn ($v, $i) => $v === null ? null : [$i, (float) $v])->filter()->values();
    $chemin = null;

    if ($points->count() >= 2) {
        $max = $points->max(fn ($p) => $p[1]);
        $min = $points->min(fn ($p) => $p[1]);
        $etendue = ($max - $min) ?: 1;
        $dernier = max(count($valeurs) - 1, 1);
        $chemin = $points->map(fn ($p) => round($p[0] / $dernier * 100, 2).','.round(26 - ($p[1] - $min) / $etendue * 22, 2))->implode(' ');
    }
@endphp

@if ($chemin)
    <svg {{ $attributes->class('nt-sparkline') }} viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <polyline points="{{ $chemin }}" fill="none" vector-effect="non-scaling-stroke" />
    </svg>
@endif
