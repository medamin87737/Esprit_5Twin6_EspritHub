@extends('layouts.admin')

@section('title', 'Types d\'acteurs')

@section('content')
    @php($typeActeurs = $typeActeurs ?? collect())

    <x-admin.page-header title="Types d'acteurs" module="Module 2 · Acteurs de la chaîne"
                         subtitle="Les rôles de la chaîne d'approvisionnement : producteur, transformateur, distributeur…">
        <x-slot:actions>
            <a href="{{ route('admin.type-acteurs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouveau type
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$typeActeurs" title="Liste des types d'acteurs" search-placeholder="Rechercher un type…">
        <x-slot:head>
            <th scope="col">Libellé</th>
            <th scope="col">Rôle dans la chaîne</th>
            <th scope="col" class="text-center">Acteurs</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($typeActeurs as $typeActeur)
            <tr>
                <td>
                    <div class="nt-cell-title">{{ $typeActeur->libelle }}</div>
                    <div class="nt-cell-sub">{{ \Illuminate\Support\Str::limit($typeActeur->description, 70) ?: '—' }}</div>
                </td>
                <td class="text-muted">{{ $typeActeur->role_chaine }}</td>
                <td class="text-center font-weight-600">{{ $typeActeur->acteurs_count ?? $typeActeur->acteurs->count() }}</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.type-acteurs.show', $typeActeur)"
                        :edit="route('admin.type-acteurs.edit', $typeActeur)"
                        :delete="route('admin.type-acteurs.destroy', $typeActeur)"
                        :confirm="'Supprimer le type « ' . $typeActeur->libelle . ' » ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-diagram-3" title="Aucun type d'acteur défini">
                Définissez les rôles de la chaîne (producteur, transformateur, distributeur) avant d'enregistrer des acteurs.
                <x-slot:action>
                    <a href="{{ route('admin.type-acteurs.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Créer un type
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
