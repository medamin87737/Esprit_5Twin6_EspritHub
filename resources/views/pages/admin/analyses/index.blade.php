@extends('layouts.admin')

@section('title', 'Analyses')

@section('content')
    @php
        $types = config('nutritrace.options.analyse_types');
        $resultats = config('nutritrace.options.analyse_resultats');
    @endphp

    <x-admin.page-header title="Analyses qualité" module="Module 2 · Analyses qualité"
                         subtitle="Les contrôles sanitaires des lots, le laboratoire qui les réalise et leur résultat.">
        <x-slot:actions>
            <a href="{{ route('admin.analyses.create', request()->only('lot')) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouvelle analyse
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$analyses" title="Liste des analyses" search-placeholder="Numéro, lot ou produit…"
                        :filter-keys="['q', 'resultat', 'type', 'laboratoire', 'lot']">
        <x-slot:filters>
            <label for="filter-type" class="sr-only">Type</label>
            <select id="filter-type" name="type" class="custom-select">
                <option value="">Tous les types</option>
                @foreach ($types as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(request('type') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
            <label for="filter-resultat" class="sr-only">Résultat</label>
            <select id="filter-resultat" name="resultat" class="custom-select">
                <option value="">Tous les résultats</option>
                @foreach ($resultats as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(request('resultat') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
            <label for="filter-laboratoire" class="sr-only">Laboratoire</label>
            <select id="filter-laboratoire" name="laboratoire" class="custom-select">
                <option value="">Tous les laboratoires</option>
                @foreach ($laboratoires as $laboratoire)
                    <option value="{{ $laboratoire->id }}" @selected((string) request('laboratoire') === (string) $laboratoire->id)>{{ $laboratoire->nom }}</option>
                @endforeach
            </select>
            @if (request()->filled('lot'))
                <input type="hidden" name="lot" value="{{ request('lot') }}">
            @endif
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Numéro</th>
            <th scope="col">Type</th>
            <th scope="col">Lot</th>
            <th scope="col">Laboratoire</th>
            <th scope="col">Prélèvement</th>
            <th scope="col">Résultat</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($analyses as $analyse)
            <tr>
                <td class="nt-cell-title">
                    {{ $analyse->numero }}
                    @if ($analyse->rapport)
                        <i class="bi bi-file-earmark-pdf text-danger ml-1" title="Rapport PDF joint" aria-label="Rapport PDF joint"></i>
                    @endif
                </td>
                <td><span class="nt-badge"><i class="bi {{ $analyse->icone() }}" aria-hidden="true"></i> {{ $analyse->typeLabel() }}</span></td>
                <td>
                    <div class="nt-cell-title">{{ $analyse->lot?->numero_lot ?? '—' }}</div>
                    <div class="nt-cell-sub">{{ $analyse->lot?->produit?->nom }}</div>
                </td>
                <td class="text-muted">{{ $analyse->laboratoire?->nom ?? '—' }}</td>
                <td class="text-muted">{{ $analyse->date_prelevement?->format('d/m/Y') }}</td>
                <td>@include('pages.admin.analyses._resultat')</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.analyses.show', $analyse)"
                        :edit="route('admin.analyses.edit', $analyse)"
                        :delete="route('admin.analyses.destroy', $analyse)"
                        :confirm="'Supprimer l\'analyse ' . $analyse->numero . ' ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-clipboard2-pulse" title="Aucune analyse enregistrée">
                Enregistrez le contrôle sanitaire d'un lot (microbiologie, pesticides…) réalisé par un laboratoire.
                <x-slot:action>
                    <a href="{{ route('admin.analyses.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter une analyse
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
