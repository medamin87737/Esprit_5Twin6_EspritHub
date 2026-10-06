@props(['score', 'niveau'])

@php
    $longueur = 125.66;
    $rempli = round($longueur * max(0, min(100, $score)) / 100, 2);
@endphp

<figure {{ $attributes->class('nt-gauge') }} role="img" aria-label="Indice de traçabilité : {{ $score }} %, {{ $niveau }}">
    <svg viewBox="0 0 100 56" aria-hidden="true" focusable="false">
        <path class="nt-gauge-track" d="M 10 50 A 40 40 0 0 1 90 50" />
        <path class="nt-gauge-value" d="M 10 50 A 40 40 0 0 1 90 50" stroke-dasharray="{{ $rempli }} {{ $longueur }}" />
    </svg>
    <figcaption class="nt-gauge-label">
        <span class="nt-gauge-score">{{ $score }}<small>%</small></span>
        <span class="nt-gauge-level">{{ $niveau }}</span>
    </figcaption>
</figure>
