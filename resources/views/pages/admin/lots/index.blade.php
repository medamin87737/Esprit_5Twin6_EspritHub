@extends('layouts.admin')

@section('title', 'Lots')

@section('content')
    @php
        $lots = $lots ?? collect();
        $produits = $produits ?? collect();
    @endphp

    <x-admin.page-header title="Lots" module="Module 3 · Traçabilité des lots"
                         subtitle="Chaque lot de production, son produit et ses dates clés.">
        <x-slot:actions>
            <a href="{{ route('admin.lots.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouveau lot
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$lots" title="Liste des lots" search-placeholder="Numéro de lot ou produit…" :filter-keys="['q', 'produit']">
        <x-slot:filters>
            <label for="filter-produit" class="sr-only">Produit</label>
            <select id="filter-produit" name="produit" class="custom-select">
                <option value="">Tous les produits</option>
                @foreach ($produits as $produit)
                    <option value="{{ $produit->id }}" @selected((string) request('produit') === (string) $produit->id)>{{ $produit->nom }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Numéro de lot</th>
            <th scope="col">Produit</th>
            <th scope="col" class="text-right">Quantité</th>
            <th scope="col">Production</th>
            <th scope="col">Péremption</th>
            <th scope="col" class="text-center">Étapes</th>
            <th scope="col" class="text-center">Éco-score</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($lots as $lot)
            <tr>
                <td><span class="nt-cell-title"><i class="bi bi-upc-scan text-muted mr-1" aria-hidden="true"></i>{{ $lot->numero_lot }}</span></td>
                <td>
                    @if ($lot->produit)
                        <a href="{{ route('admin.produits.show', $lot->produit) }}">{{ $lot->produit->nom }}</a>
                    @else
                        —
                    @endif
                </td>
                <td class="text-right font-weight-600">{{ number_format($lot->quantite, 0, ',', ' ') }}</td>
                <td class="text-muted">{{ $lot->date_production?->format('d/m/Y') }}</td>
                <td>
                    @if ($lot->date_peremption?->isPast())
                        <span class="nt-badge nt-badge-danger">{{ $lot->date_peremption->format('d/m/Y') }}</span>
                    @else
                        <span class="text-muted">{{ $lot->date_peremption?->format('d/m/Y') }}</span>
                    @endif
                </td>
                <td class="text-center font-weight-600">{{ $lot->etapes_count ?? $lot->etapes->count() }}</td>
                <td class="text-center">
                    @if ($lot->empreinteCarbone)
                        <span class="nt-score nt-score-{{ strtolower($lot->empreinteCarbone->score) }}">{{ $lot->empreinteCarbone->score }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.lots.show', $lot)"
                        :edit="route('admin.lots.edit', $lot)"
                        :delete="route('admin.lots.destroy', $lot)"
                        :confirm="'Supprimer le lot ' . $lot->numero_lot . ', ses étapes et son empreinte carbone ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-upc-scan" title="Aucun lot enregistré">
                Créez un lot à partir d'un produit du catalogue pour commencer à tracer son parcours.
                <x-slot:action>
                    <a href="{{ route('admin.lots.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Créer un lot
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
