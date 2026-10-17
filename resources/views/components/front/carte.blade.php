@props([
    'points' => [],
    'trace' => false,
    'titre' => 'Carte',
])

{{-- points : liste de ['lat' => float, 'lng' => float, 'titre' => string, 'texte' => ?string] --}}
@if (count($points))
    @php
        $urlGoogleMaps = null;
        if (count($points) === 1) {
            $p = $points[0];
            $urlGoogleMaps = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($p['lat'] . ',' . $p['lng']);
        } elseif (count($points) > 1) {
            $premier = $points[0];
            $dernier = $points[count($points) - 1];
            $intermediaires = array_slice($points, 1, -1);
            $waypointsParam = !empty($intermediaires)
                ? '&waypoints=' . urlencode(implode('|', array_map(fn ($pt) => $pt['lat'] . ',' . $pt['lng'], $intermediaires)))
                : '';
            $urlGoogleMaps = 'https://www.google.com/maps/dir/?api=1&origin=' . urlencode($premier['lat'] . ',' . $premier['lng'])
                . '&destination=' . urlencode($dernier['lat'] . ',' . $dernier['lng'])
                . $waypointsParam;
        }
    @endphp

    <div {{ $attributes->class(['map-card']) }}>
        <div class="map-canvas" role="img" aria-label="{{ $titre }}"
             data-carte='@json(array_values($points))' data-trace="{{ $trace ? '1' : '0' }}"></div>
        @if ($urlGoogleMaps)
            <div class="p-2 px-3 bg-white border-top d-flex justify-content-between align-items-center small">
                <span class="text-muted"><i class="bi bi-pin-map me-1 text-success"></i>{{ count($points) }} étape{{ count($points) > 1 ? 's' : '' }} géolocalisée{{ count($points) > 1 ? 's' : '' }}</span>
                <a href="{{ $urlGoogleMaps }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-map me-1"></i> {{ count($points) > 1 ? "Ouvrir l'itinéraire Google Maps" : 'Ouvrir dans Google Maps' }} <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>
        @endif
    </div>
@else
    <div {{ $attributes->class(['map-card map-card-empty']) }}>
        <i class="bi bi-geo" aria-hidden="true"></i>
        <span>Aucune coordonnée GPS disponible pour cette carte.</span>
    </div>
@endif
