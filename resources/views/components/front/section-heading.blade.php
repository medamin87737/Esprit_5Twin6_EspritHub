@props([
    'title',
    'eyebrow' => null,
    'light' => false,
    'align' => 'center',
])

<div {{ $attributes->class([
    'section-heading',
    'text-center mx-auto' => $align === 'center',
    'section-heading-light' => $light,
]) }}>
    @if ($eyebrow)
        <span class="eyebrow">{{ $eyebrow }}</span>
    @endif
    <h2 class="mt-0">{{ $title }}</h2>
    <hr @class(['divider', 'divider-light' => $light, 'ms-0' => $align !== 'center'])>
    @if ($slot->isNotEmpty())
        <p class="lead-text mb-0">{{ $slot }}</p>
    @endif
</div>
