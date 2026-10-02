@extends('layouts.front')

@section('title', 'Comparer les éco-scores')

@section('content')
    @php
        $empreintes = $empreintes ?? collect();
        $scores = config('nutritrace.options.scores');
        $filtered = request()->filled('q') || request()->filled('score');
        $total = $empreintes instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $empreintes->total() : $empreintes->count();
    @endphp

    <x-front.page-header eyebrow="Éco-score" title="Comparer l'impact des produits" image="serre-plants.webp"
                         subtitle="Chaque lot reçoit un score de A à E calculé à partir de ses émissions de CO₂ mesurées." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="filter-bar">
                <h2 class="h6 mb-3">Comment lire l'éco-score ?</h2>
                <div class="score-legend">
                    @foreach ($scores as $score => $seuil)
                        <div class="score-legend-item">
                            <span class="score-badge eco-{{ strtolower($score) }}">{{ $score }}</span>
                            <span>{{ $seuil }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <form class="d-flex flex-wrap gap-2 align-items-end mt-5" method="GET" action="{{ route('front.empreintes.index') }}" role="search">
                <div class="flex-grow-1" style="min-width: 14rem;">
                    <label class="form-label" for="q">Produit</label>
                    <div class="field-icon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input class="form-control" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit…">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="score">Score</label>
                    <select class="form-select" id="score" name="score">
                        <option value="">Tous</option>
                        @foreach (array_keys($scores) as $score)
                            <option value="{{ $score }}" @selected(request('score') === $score)>{{ $score }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Filtrer</button>
            </form>

            <div class="results-bar">
                <span><strong>{{ $total }}</strong> empreinte{{ $total > 1 ? 's' : '' }} calculée{{ $total > 1 ? 's' : '' }}</span>
                @if ($filtered)
                    <a href="{{ route('front.empreintes.index') }}" class="small"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Effacer les filtres</a>
                @endif
            </div>

            @if ($total === 0)
                @if ($filtered)
                    <x-front.empty-state icon="bi-search" title="Aucune empreinte ne correspond" :reset="route('front.empreintes.index')">
                        Essayez un autre produit ou un autre score.
                    </x-front.empty-state>
                @else
                    <x-front.empty-state icon="bi-cloud-haze2" title="Aucune empreinte publiée pour l'instant">
                        Les éco-scores des lots apparaîtront ici dès que leurs émissions auront été calculées.
                    </x-front.empty-state>
                @endif
            @else
                <div class="data-card">
                    <div class="table-responsive">
                        <table class="table data-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Produit</th>
                                    <th scope="col">Lot</th>
                                    <th scope="col" class="text-end">CO₂ total</th>
                                    <th scope="col" class="text-center">Score</th>
                                    <th scope="col">Détail mesuré</th>
                                    <th scope="col">Méthode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($empreintes as $empreinte)
                                    <tr>
                                        <td class="fw-semibold">{{ $empreinte->lot?->produit?->nom }}</td>
                                        <td><a href="{{ route('front.lots.search', ['numero' => $empreinte->lot?->numero_lot]) }}" class="text-muted">{{ $empreinte->lot?->numero_lot }}</a></td>
                                        <td class="text-end">{{ number_format($empreinte->co2_total, 2, ',', ' ') }} kg</td>
                                        <td class="text-center"><span class="score-badge eco-{{ strtolower($empreinte->score) }}">{{ $empreinte->score }}</span></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @forelse ($empreinte->indicateurs->groupBy('type') as $type => $groupe)
                                                    <span class="label-chip" title="{{ $groupe->first()->typeLabel() }}">
                                                        <i class="bi {{ $groupe->first()->icone() }}" aria-hidden="true"></i>
                                                        {{ number_format($groupe->sum('valeur'), 0, ',', ' ') }} {{ $groupe->first()->unite }}
                                                    </span>
                                                @empty
                                                    <span class="text-muted small">—</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="text-muted">{{ $empreinte->methode }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($empreintes instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $empreintes->hasPages())
                    <div class="mt-5 d-flex justify-content-center">{{ $empreintes->links('pagination::bootstrap-5') }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
