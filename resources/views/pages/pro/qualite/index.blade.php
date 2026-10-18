@extends('layouts.pro')

@section('title', 'Qualité')

@section('pro_content')
    <div class="pro-toolbar">
        <div>
            <h2 class="h4 mb-1"><i class="bi bi-clipboard2-pulse me-2 text-success" aria-hidden="true"></i>Qualité</h2>
            <p class="text-muted mb-0 small">Déclarez une analyse laboratoire sur un lot que vous détenez.</p>
        </div>
    </div>

    @if ($lots->isEmpty())
        <x-front.empty-state icon="bi-clipboard2-pulse" title="Aucun lot en votre possession">
            Créez ou recevez un lot, puis revenez ici pour y ajouter une analyse qualité.
        </x-front.empty-state>
        <div class="text-center mt-3">
            <a href="{{ route('pro.lots.index') }}" class="btn btn-outline-primary"><i class="bi bi-upc-scan me-1" aria-hidden="true"></i>Mes lots</a>
            @can('pro-creer-lot')
                <a href="{{ route('pro.lots.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Créer un lot</a>
            @endcan
        </div>
    @else
        <div class="row g-3">
            @foreach ($lots as $lot)
                <div class="col-md-6">
                    <div class="panel h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="fw-semibold">{{ $lot->produit?->nom }}</div>
                                <div class="small text-muted"><i class="bi bi-upc me-1" aria-hidden="true"></i>{{ $lot->numero_lot }}</div>
                            </div>
                            <span class="status-pill status-pending">{{ $lot->analyses_count }} analyse(s)</span>
                        </div>

                        @if ($lot->analyses->isNotEmpty())
                            <ul class="small text-muted mb-3 ps-3">
                                @foreach ($lot->analyses as $analyse)
                                    <li>{{ $analyse->typeLabel() }} · {{ $analyse->resultatLabel() }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="small text-muted mb-3">Pas encore d'analyse sur ce lot.</p>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('pro.lots.show', $lot) }}" class="btn btn-sm btn-outline-secondary">Voir le lot</a>
                            @can('ajouterAnalyse', $lot)
                                <a href="{{ route('pro.analyses.create', $lot) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Ajouter une analyse
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $lots->links() }}</div>
    @endif
@endsection
