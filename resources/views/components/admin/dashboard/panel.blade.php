@props(['titre', 'sousTitre' => null, 'id' => null])

<section {{ $attributes->class('nt-panel') }} @if ($id) aria-labelledby="{{ $id }}" @endif>
    <header class="nt-panel-header">
        <div class="nt-panel-heading">
            <h2 class="nt-panel-title" @if ($id) id="{{ $id }}" @endif>{{ $titre }}</h2>
            @if ($sousTitre)
                <p class="nt-panel-subtitle">{{ $sousTitre }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="nt-panel-actions">{{ $actions }}</div>
        @endisset
    </header>

    <div class="nt-panel-body">
        {{ $slot }}
    </div>
</section>
