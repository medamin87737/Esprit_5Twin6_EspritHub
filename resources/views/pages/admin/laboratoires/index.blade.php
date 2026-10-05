@extends('layouts.admin')

@section('title', 'Laboratoires')

@section('content')
    <x-admin.page-header title="Laboratoires d'analyse" module="Module 2 · Analyses qualité"
                         subtitle="Les laboratoires accrédités qui contrôlent la qualité sanitaire des lots.">
        <x-slot:actions>
            <a href="{{ route('admin.laboratoires.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouveau laboratoire
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$laboratoires" title="Liste des laboratoires" search-placeholder="Nom ou accréditation…" :filter-keys="['q', 'ville']">
        <x-slot:filters>
            <label for="filter-ville" class="sr-only">Ville</label>
            <select id="filter-ville" name="ville" class="custom-select">
                <option value="">Toutes les villes</option>
                @foreach ($villes as $ville)
                    <option value="{{ $ville }}" @selected(request('ville') === $ville)>{{ $ville }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Laboratoire</th>
            <th scope="col">Ville</th>
            <th scope="col">Accréditation</th>
            <th scope="col" class="text-center">Analyses</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($laboratoires as $laboratoire)
            <tr>
                <td>
                    <div class="nt-cell-title">{{ $laboratoire->nom }}</div>
                    <div class="nt-cell-sub">{{ $laboratoire->email }} · {{ $laboratoire->telephone }}</div>
                </td>
                <td>{{ $laboratoire->ville }} <span class="nt-cell-sub">({{ $laboratoire->pays }})</span></td>
                <td><span class="nt-badge nt-badge-info">{{ $laboratoire->accreditation }}</span></td>
                <td class="text-center">
                    <span class="font-weight-600">{{ $laboratoire->analyses_count }}</span>
                    @if ($laboratoire->non_conformes_count > 0)
                        <div class="nt-cell-sub text-danger">{{ $laboratoire->non_conformes_count }} non conforme(s)</div>
                    @endif
                </td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.laboratoires.show', $laboratoire)"
                        :edit="route('admin.laboratoires.edit', $laboratoire)"
                        :delete="route('admin.laboratoires.destroy', $laboratoire)"
                        :confirm="'Supprimer le laboratoire « ' . $laboratoire->nom . ' » ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-building-check" title="Aucun laboratoire enregistré">
                Ajoutez les laboratoires accrédités avant d'enregistrer les analyses qu'ils réalisent.
                <x-slot:action>
                    <a href="{{ route('admin.laboratoires.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un laboratoire
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
