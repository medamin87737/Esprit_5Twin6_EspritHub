@props([
    'score' => null,
    'co2' => null,
    'taille' => 'md',
])

@if ($score)
    <span {{ $attributes->class(['score-wrap', 'score-wrap-' . $taille]) }}>
        <span class="score-badge score-{{ $taille }} eco-{{ strtolower($score) }}" title="Score {{ $score }} : {{ config('nutritrace.options.scores.' . $score) }}">
            <span class="visually-hidden">Score </span>{{ $score }}
        </span>
        @if ($co2 !== null)
            <span class="score-co2">{{ number_format($co2, 2, ',', ' ') }} kg CO₂e</span>
        @endif
    </span>
@else
    <span {{ $attributes->class(['label-chip', 'score-none']) }} title="Aucune empreinte carbone calculée pour ce produit">
        <i class="bi bi-hourglass-split" aria-hidden="true"></i> Score non calculé
    </span>
@endif
