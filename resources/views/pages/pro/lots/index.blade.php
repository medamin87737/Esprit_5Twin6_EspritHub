@extends('layouts.pro')

@section('title', 'Mes lots')

@section('pro_content')
    <div class="pro-toolbar">
        <ul class="nav tabs-pro gap-2">
            <li class="nav-item"><a @class(['nav-link', 'active' => ! $historique]) href="{{ route('pro.lots.index') }}">En ma possession</a></li>
            <li class="nav-item"><a @class(['nav-link', 'active' => $historique]) href="{{ route('pro.lots.index', ['vue' => 'historique']) }}">Transférés</a></li>
        </ul>
        @can('pro-creer-lot')
            <a href="{{ route('pro.lots.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nouveau lot</a>
        @endcan
    </div>

    @if ($lots->isEmpty())
        @if ($historique)
            <x-front.empty-state icon="bi-signpost-split" title="Aucun lot transféré">
                Les lots que vous avez traités puis transmis à l'acteur suivant apparaîtront ici, en lecture seule.
            </x-front.empty-state>
        @else
            <x-front.empty-state icon="bi-box-arrow-in-down" title="Aucun lot en votre possession">
                @can('pro-creer-lot')
                    Créez un lot pour l'un de vos produits, puis enregistrez votre étape.
                @else
                    Les lots apparaîtront ici dès qu'un acteur de la chaîne vous les aura transférés.
                @endcan
            </x-front.empty-state>
        @endif
    @else
        <div class="data-card">
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Lot</th>
                            <th scope="col">Quantité</th>
                            <th scope="col">Étapes</th>
                            <th scope="col">Score</th>
                            <th scope="col">{{ $historique ? 'Détenu par' : 'Péremption' }}</th>
                            <th scope="col"><span class="visually-hidden">Détail</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lots as $lot)
                            <tr>
                                <td>
                                    <div class="cell-title">{{ $lot->numero_lot }}</div>
                                    <div class="cell-sub">{{ $lot->produit?->nom }}</div>
                                </td>
                                <td>{{ number_format($lot->quantite, 0, ',', ' ') }}</td>
                                <td>{{ $lot->etapes_count }}</td>
                                <td><x-front.score-badge :score="$lot->empreinteCarbone?->score" taille="sm" /></td>
                                <td>
                                    @if ($historique)
                                        {{ $lot->acteurCourant?->nom ?? '—' }}
                                    @else
                                        {{ $lot->date_peremption?->format('d/m/Y') }}
                                        @if ($lot->estPerime()) <span class="status-pill status-expired ms-1">Périmé</span> @endif
                                    @endif
                                </td>
                                <td class="cell-actions"><a href="{{ route('pro.lots.show', $lot) }}" class="btn btn-sm btn-outline-primary">Détail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-front.pagination :items="$lots" />
    @endif
@endsection
