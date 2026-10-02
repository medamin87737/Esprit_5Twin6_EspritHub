@props([
    'items',
    'title',
    'searchPlaceholder' => null,
    'filterKeys' => ['q'],
])

@php
    $total = $items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $items->total() : count($items);
    $filtered = request()->hasAny($filterKeys) && collect(request()->only($filterKeys))->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

<div {{ $attributes->class(['card mb-4']) }}>
    <div class="card-header nt-card-header">
        <div>
            <h2 class="nt-card-title">{{ $title }}</h2>
            <span class="nt-card-subtitle">{{ $total }} enregistrement{{ $total > 1 ? 's' : '' }}</span>
        </div>

        @if ($searchPlaceholder)
            <form method="GET" action="{{ url()->current() }}" class="nt-toolbar" role="search">
                <div class="nt-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label for="table-search" class="sr-only">Rechercher</label>
                    <input id="table-search" type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ $searchPlaceholder }}">
                </div>
                {{ $filters ?? '' }}
                <button type="submit" class="btn btn-light"><i class="bi bi-funnel" aria-hidden="true"></i><span class="sr-only">Filtrer</span></button>
            </form>
        @endif
    </div>

    @if ($total === 0)
        @if ($filtered)
            <x-admin.empty-state icon="bi-search" title="Aucun résultat">
                Aucun enregistrement ne correspond à vos critères de recherche.
                <x-slot:action>
                    <a href="{{ url()->current() }}" class="btn btn-light btn-sm"><i class="bi bi-x-lg mr-1" aria-hidden="true"></i> Réinitialiser les filtres</a>
                </x-slot:action>
            </x-admin.empty-state>
        @else
            {{ $empty }}
        @endif
    @else
        <div class="table-responsive">
            <table class="table nt-table">
                <thead>
                    <tr>{{ $head }}</tr>
                </thead>
                <tbody>
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        @if ($items instanceof \Illuminate\Contracts\Pagination\Paginator && $items->hasPages())
            <div class="card-footer">{{ $items->withQueryString()->links() }}</div>
        @endif
    @endif
</div>
