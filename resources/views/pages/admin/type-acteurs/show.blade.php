@extends('layouts.admin')

@section('title', $typeActeur->libelle)

@section('content')
    <x-admin.page-header :title="$typeActeur->libelle" module="Administration · Type d'acteur"
                         :subtitle="$typeActeur->role_chaine" :back="route('admin.type-acteurs.index')">
        <x-slot:actions>
            <a href="{{ route('admin.type-acteurs.edit', $typeActeur) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-header"><h2 class="nt-card-title">Informations</h2></div>
                <div class="card-body">
                    <p class="{{ $typeActeur->description ? '' : 'text-muted' }}">{{ $typeActeur->description ?: 'Aucune description.' }}</p>
                    <div class="nt-role-row"><span class="text-muted">Acteurs</span><strong>{{ $typeActeur->acteurs->count() }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Créé le</span><strong>{{ $typeActeur->created_at?->format('d/m/Y') }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <x-admin.table-card :items="$typeActeur->acteurs" title="Acteurs de ce type">
                <x-slot:head>
                    <th scope="col">Acteur</th>
                    <th scope="col">Pays</th>
                    <th scope="col" class="text-center">Produits</th>
                    <th scope="col" class="text-right">Actions</th>
                </x-slot:head>

                @foreach ($typeActeur->acteurs as $acteur)
                    <tr>
                        <td>
                            <div class="nt-cell-title">{{ $acteur->nom }}</div>
                            <div class="nt-cell-sub">{{ $acteur->email }}</div>
                        </td>
                        <td>{{ $acteur->pays }}</td>
                        <td class="text-center font-weight-600">{{ $acteur->produits_count }}</td>
                        <td class="text-right">
                            <x-admin.row-actions :show="route('admin.acteurs.show', $acteur)" :edit="route('admin.acteurs.edit', $acteur)" />
                        </td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-person-badge" title="Aucun acteur de ce type">
                        Les acteurs rattachés à « {{ $typeActeur->libelle }} » apparaîtront ici.
                        <x-slot:action>
                            <a href="{{ route('admin.acteurs.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un acteur
                            </a>
                        </x-slot:action>
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>
        </div>
    </div>
@endsection
