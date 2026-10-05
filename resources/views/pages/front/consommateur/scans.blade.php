@extends('layouts.front')

@section('title', 'Historique des scans')

@section('content')
    <x-front.page-header eyebrow="Mon espace" title="Historique des scans" image="consommateur-bowl.webp"
                         subtitle="Les lots que vous avez scannés ou consultés, du plus récent au plus ancien." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            @if ($scans->isEmpty())
                <x-front.empty-state icon="bi-clock-history" title="Aucun scan pour le moment">
                    Scannez ou saisissez le numéro d'un lot : il apparaîtra ici pour le retrouver facilement.
                </x-front.empty-state>
                <div class="text-center mt-3">
                    <a href="{{ route('front.lots.search') }}" class="btn btn-primary rounded-pill px-4"><i class="bi bi-upc-scan me-1" aria-hidden="true"></i>Scanner un lot</a>
                </div>
            @else
                <div class="data-card">
                    <div class="table-responsive">
                        <table class="table data-table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Produit</th>
                                    <th scope="col">Lot</th>
                                    <th scope="col">Score</th>
                                    <th scope="col">Scanné le</th>
                                    <th scope="col"><span class="visually-hidden">Parcours</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($scans as $scan)
                                    <tr>
                                        <td class="cell-title">{{ $scan->lot?->produit?->nom }}</td>
                                        <td>{{ $scan->lot?->numero_lot }}</td>
                                        <td><x-front.score-badge :score="$scan->lot?->empreinteCarbone?->score" taille="sm" /></td>
                                        <td>{{ $scan->created_at?->format('d/m/Y à H:i') }}</td>
                                        <td class="cell-actions">
                                            @if ($scan->lot)
                                                <a href="{{ route('front.lots.show', $scan->lot) }}" class="btn btn-sm btn-outline-primary">Revoir le parcours</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <x-front.pagination :items="$scans" />
            @endif
        </div>
    </section>
@endsection
