@props([
    'icon' => 'bi-inbox',
    'title',
    'reset' => null,
])

<div {{ $attributes->class(['empty-panel']) }}>
    <span class="empty-panel-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
    <h2 class="h5">{{ $title }}</h2>
    <p>{{ $slot }}</p>
    @if ($reset)
        <a href="{{ $reset }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-x-lg me-1" aria-hidden="true"></i> Effacer les filtres
        </a>
    @endif
</div>
