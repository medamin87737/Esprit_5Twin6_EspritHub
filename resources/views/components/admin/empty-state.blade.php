@props([
    'icon' => 'bi-inbox',
    'title',
])

<div {{ $attributes->class(['nt-empty']) }}>
    <span class="nt-empty-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
    <h3>{{ $title }}</h3>
    <p>{{ $slot }}</p>
    @isset($action)
        {{ $action }}
    @endisset
</div>
