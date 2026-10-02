@props([
    'title',
    'subtitle' => null,
    'module' => null,
    'back' => null,
    'backLabel' => 'Retour à la liste',
])

<div {{ $attributes->class(['nt-page-header']) }}>
    <div>
        @if ($back)
            <a href="{{ $back }}" class="nt-back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> {{ $backLabel }}</a>
        @endif
        @if ($module)
            <span class="nt-eyebrow">{{ $module }}</span>
        @endif
        <h1 class="nt-page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="nt-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="nt-page-actions">{{ $actions }}</div>
    @endisset
</div>
