@props([
    'number',
    'icon',
    'title',
    'status' => null,
    'statusType' => 'valid',
    'meta' => null,
])

<li {{ $attributes->class(['timeline-step']) }}>
    <div class="timeline-marker" aria-hidden="true">
        <i class="bi {{ $icon }}"></i>
        <span class="timeline-number">{{ $number }}</span>
    </div>
    <div class="timeline-content">
        <h3 class="timeline-title"><span class="visually-hidden">Étape {{ $number }} : </span>{{ $title }}</h3>
        <p class="timeline-text">{{ $slot }}</p>
        <div class="timeline-meta">
            @if ($status)
                <span class="status-pill status-{{ $statusType }}">{{ $status }}</span>
            @endif
            @if ($meta)
                <span class="timeline-date"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $meta }}</span>
            @endif
        </div>
    </div>
</li>
