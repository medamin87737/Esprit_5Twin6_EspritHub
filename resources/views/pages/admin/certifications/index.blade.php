@extends('layouts.admin')

@section('title', 'Certifications')

@section('content')
    @php
        $certifications = $certifications ?? collect();
        $organismes = $organismes ?? collect();
        $types = config('nutritrace.options.certification_types');
        $statuts = config('nutritrace.options.certification_statuts');
    @endphp

    <x-admin.page-header title="Certifications" module="Module 5 · Certifications"
                         subtitle="Les labels attribués aux produits, l'organisme qui les délivre et leur validité.">
        <x-slot:actions>
            <a href="{{ route('admin.certifications.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouvelle certification
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$certifications" title="Liste des certifications" search-placeholder="Numéro ou produit…"
                        :filter-keys="['q', 'statut', 'type', 'organisme']">
        <x-slot:filters>
            <label for="filter-type" class="sr-only">Type</label>
            <select id="filter-type" name="type" class="custom-select">
                <option value="">Tous les labels</option>
                @foreach ($types as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(request('type') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
            <label for="filter-statut" class="sr-only">Statut</label>
            <select id="filter-statut" name="statut" class="custom-select">
                <option value="">Tous les statuts</option>
                @foreach ($statuts as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
            <label for="filter-organisme" class="sr-only">Organisme</label>
            <select id="filter-organisme" name="organisme" class="custom-select">
                <option value="">Tous les organismes</option>
                @foreach ($organismes as $organisme)
                    <option value="{{ $organisme->id }}" @selected((string) request('organisme') === (string) $organisme->id)>{{ $organisme->nom }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Numéro</th>
            <th scope="col">Label</th>
            <th scope="col">Produit</th>
            <th scope="col">Organisme</th>
            <th scope="col">Expiration</th>
            <th scope="col">Statut</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($certifications as $certification)
            <tr>
                <td class="nt-cell-title">{{ $certification->numero }}</td>
                <td><span class="nt-badge"><i class="bi {{ $certification->icone() }}" aria-hidden="true"></i> {{ $certification->typeLabel() }}</span></td>
                <td>{{ $certification->produit?->nom ?? '—' }}</td>
                <td class="text-muted">{{ $certification->organisme?->nom ?? '—' }}</td>
                <td class="text-muted">{{ $certification->date_expiration?->format('d/m/Y') }}</td>
                <td>@include('pages.admin.certifications._statut')</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.certifications.show', $certification)"
                        :edit="route('admin.certifications.edit', $certification)"
                        :delete="route('admin.certifications.destroy', $certification)"
                        :confirm="'Supprimer la certification ' . $certification->numero . ' ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-award" title="Aucune certification enregistrée">
                Attribuez un label (bio, local, équitable) à un produit, délivré par un organisme certificateur.
                <x-slot:action>
                    <a href="{{ route('admin.certifications.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter une certification
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
