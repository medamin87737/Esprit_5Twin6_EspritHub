@extends('layouts.admin')

@section('title', 'Étapes')

@section('content')
    @php
        $etapes = $etapes ?? collect();
        $types = config('nutritrace.options.etape_types');
        $transports = config('nutritrace.options.modes_transport');
    @endphp

    <x-admin.page-header title="Étapes" module="Module 3 · Traçabilité des lots"
                         subtitle="Les passages de chaque lot entre les acteurs de la chaîne.">
        <x-slot:actions>
            <a href="{{ route('admin.etapes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouvelle étape
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$etapes" title="Liste des étapes" search-placeholder="Lot, acteur ou lieu…" :filter-keys="['q', 'type_etape']">
        <x-slot:filters>
            <label for="filter-type" class="sr-only">Type d'étape</label>
            <select id="filter-type" name="type_etape" class="custom-select">
                <option value="">Tous les types</option>
                @foreach ($types as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(request('type_etape') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Lot</th>
            <th scope="col">Type</th>
            <th scope="col">Acteur</th>
            <th scope="col">Lieu</th>
            <th scope="col">Date et heure</th>
            <th scope="col">Transport</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($etapes as $etape)
            <tr>
                <td>
                    @if ($etape->lot)
                        <a href="{{ route('admin.lots.show', $etape->lot) }}" class="nt-cell-title">{{ $etape->lot->numero_lot }}</a>
                        <div class="nt-cell-sub">{{ $etape->lot->produit?->nom }}</div>
                    @else
                        —
                    @endif
                </td>
                <td><span class="nt-badge">{{ $types[$etape->type_etape] ?? $etape->type_etape }}</span></td>
                <td>
                    @if ($etape->acteur)
                        <a href="{{ route('admin.acteurs.show', $etape->acteur) }}">{{ $etape->acteur->nom }}</a>
                    @else
                        —
                    @endif
                </td>
                <td class="text-muted">{{ $etape->lieu }}</td>
                <td class="text-muted">{{ $etape->date_heure?->format('d/m/Y H:i') }}</td>
                <td class="text-muted">{{ $transports[$etape->mode_transport] ?? '—' }}</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.etapes.show', $etape)"
                        :edit="route('admin.etapes.edit', $etape)"
                        :delete="route('admin.etapes.destroy', $etape)"
                        confirm="Supprimer cette étape du parcours ?" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-geo-alt" title="Aucune étape enregistrée">
                Ajoutez les étapes (production, transformation, distribution, vente) pour reconstituer le parcours d'un lot.
                <x-slot:action>
                    <a href="{{ route('admin.etapes.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter une étape
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
