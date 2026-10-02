@props([
    'title' => 'Règles de validation',
    'icon' => 'bi-shield-check',
    'rules' => [],
    'parent' => null,
    'child' => null,
])

<div {{ $attributes->class(['card nt-help-card mb-4']) }}>
    <div class="card-body">
        <h2 class="nt-help-title"><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $title }}</h2>
        <ul class="nt-rule-list">
            @foreach ($rules as $champ => $regle)
                <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span><strong>{{ $champ }}</strong> — {{ $regle }}</span></li>
            @endforeach
        </ul>

        @if ($parent && $child)
            <div class="nt-relation mt-3">
                <span class="nt-relation-node">{{ $parent }}</span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                <span class="text-muted small">1 → N</span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                <span class="nt-relation-node">{{ $child }}</span>
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
