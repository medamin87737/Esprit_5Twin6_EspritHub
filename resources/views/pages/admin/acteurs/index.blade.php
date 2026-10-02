@extends('layouts.admin')

@section('title', 'Acteurs')

@section('content')
    @php
        $acteurs = $acteurs ?? collect();
        $typeActeurs = $typeActeurs ?? collect();
    @endphp

    <x-admin.page-header title="Acteurs" module="Module 2 · Acteurs de la chaîne"
                         subtitle="Les exploitations, usines et distributeurs qui interviennent sur les lots.">
        <x-slot:actions>
            <a href="{{ route('admin.acteurs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouvel acteur
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$acteurs" title="Liste des acteurs" search-placeholder="Nom, pays ou e-mail…" :filter-keys="['q', 'type']">
        <x-slot:filters>
            <label for="filter-type" class="sr-only">Type d'acteur</label>
            <select id="filter-type" name="type" class="custom-select">
                <option value="">Tous les types</option>
                @foreach ($typeActeurs as $typeActeur)
                    <option value="{{ $typeActeur->id }}" @selected((string) request('type') === (string) $typeActeur->id)>{{ $typeActeur->libelle }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Acteur</th>
            <th scope="col">Type</th>
            <th scope="col">Localisation</th>
            <th scope="col">Téléphone</th>
            <th scope="col" class="text-center">Produits</th>
            <th scope="col">Inscription</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($acteurs as $acteur)
            <tr>
                <td>
                    <div class="nt-cell-title">{{ $acteur->nom }}</div>
                    <div class="nt-cell-sub">{{ $acteur->email }}</div>
                </td>
                <td><span class="nt-badge">{{ $acteur->typeActeur?->libelle ?? '—' }}</span></td>
                <td>
                    <div>{{ $acteur->pays }}</div>
                    <div class="nt-cell-sub">{{ $acteur->adresse }}</div>
                </td>
                <td class="text-muted">{{ $acteur->telephone }}</td>
                <td class="text-center font-weight-600">{{ $acteur->produits_count ?? $acteur->produits->count() }}</td>
                <td class="text-muted">{{ $acteur->date_inscription?->format('d/m/Y') }}</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.acteurs.show', $acteur)"
                        :edit="route('admin.acteurs.edit', $acteur)"
                        :delete="route('admin.acteurs.destroy', $acteur)"
                        :confirm="'Supprimer l\'acteur « ' . $acteur->nom . ' » ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-person-badge" title="Aucun acteur enregistré">
                Enregistrez les producteurs, transformateurs et distributeurs qui interviendront dans les étapes des lots.
                <x-slot:action>
                    <a href="{{ route('admin.acteurs.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un acteur
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
