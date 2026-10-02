@extends('layouts.admin')

@section('title', 'Catégories')

@section('content')
    @php
        $categories = $categories ?? collect();
        $types = config('nutritrace.options.categorie_types');
    @endphp

    <x-admin.page-header title="Catégories" module="Module 1 · Produits & Catégories"
                         subtitle="Les familles de produits qui structurent le catalogue NutriTrace.">
        <x-slot:actions>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouvelle catégorie
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$categories" title="Liste des catégories" search-placeholder="Rechercher une catégorie…" :filter-keys="['q', 'type']">
        <x-slot:filters>
            <label for="filter-type" class="sr-only">Type</label>
            <select id="filter-type" name="type" class="custom-select">
                <option value="">Tous les types</option>
                @foreach ($types as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(request('type') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Catégorie</th>
            <th scope="col">Type</th>
            <th scope="col" class="text-center">Produits</th>
            <th scope="col">Créée le</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($categories as $categorie)
            <tr>
                <td>
                    <div class="nt-cell-title">{{ $categorie->nom }}</div>
                    <div class="nt-cell-sub">{{ \Illuminate\Support\Str::limit($categorie->description, 70) ?: '—' }}</div>
                </td>
                <td><span class="nt-badge">{{ $types[$categorie->type] ?? $categorie->type }}</span></td>
                <td class="text-center font-weight-600">{{ $categorie->produits_count ?? $categorie->produits->count() }}</td>
                <td class="text-muted">{{ $categorie->created_at?->format('d/m/Y') }}</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.categories.show', $categorie)"
                        :edit="route('admin.categories.edit', $categorie)"
                        :delete="route('admin.categories.destroy', $categorie)"
                        :confirm="'Supprimer la catégorie « ' . $categorie->nom . ' » ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-tags" title="Aucune catégorie pour le moment">
                Créez une première catégorie (fruits, laitiers, céréales…) pour pouvoir y rattacher des produits.
                <x-slot:action>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Créer une catégorie
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
