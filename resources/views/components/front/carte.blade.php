@props([
    'points' => [],
    'trace' => false,
    'titre' => 'Carte',
])

{{-- points : liste de ['lat' => float, 'lng' => float, 'titre' => string, 'texte' => ?string] --}}
@if (count($points))
    <div {{ $attributes->class(['map-card']) }}>
        <div class="map-canvas" role="img" aria-label="{{ $titre }}"
             data-carte='@json(array_values($points))' data-trace="{{ $trace ? '1' : '0' }}"></div>
    </div>
@else
    <div {{ $attributes->class(['map-card map-card-empty']) }}>
        <i class="bi bi-geo" aria-hidden="true"></i>
        <span>Aucune coordonnée GPS disponible pour cette carte.</span>
    </div>
@endif
