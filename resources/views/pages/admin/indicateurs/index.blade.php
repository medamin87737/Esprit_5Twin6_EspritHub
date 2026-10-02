@extends('layouts.admin')

@section('title', 'Indicateurs')

@section('content')
    @php
        $indicateurs = $indicateurs ?? collect();
        $types = config('nutritrace.options.indicateur_types');
        $icones = ['eau' => 'bi-droplet', 'energie' => 'bi-lightning-charge', 'transport' => 'bi-truck', 'emballage' => 'bi-box2'];
    @endphp

    <x-admin.page-header title="Indicateurs" module="Module 4 · Empreinte environnementale"
                         subtitle="Le détail de chaque empreinte : eau, énergie, transport et emballage.">
        <x-slot:actions>
            <a href="{{ route('admin.indicateurs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouvel indicateur
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$indicateurs" title="Liste des indicateurs" search-placeholder="Numéro de lot…" :filter-keys="['q', 'type']">
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
            <th scope="col">Type</th>
            <th scope="col" class="text-right">Valeur</th>
            <th scope="col">Empreinte (lot)</th>
            <th scope="col">Étape liée</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($indicateurs as $indicateur)
            <tr>
                <td>
                    <span class="nt-badge"><i class="bi {{ $icones[$indicateur->type] ?? 'bi-speedometer2' }}" aria-hidden="true"></i> {{ $types[$indicateur->type] ?? $indicateur->type }}</span>
                </td>
                <td class="text-right font-weight-600">{{ number_format($indicateur->valeur, 2, ',', ' ') }} {{ $indicateur->unite }}</td>
                <td>
                    @if ($indicateur->empreinteCarbone)
                        <a href="{{ route('admin.empreintes.show', $indicateur->empreinteCarbone) }}" class="nt-cell-title">{{ $indicateur->empreinteCarbone->lot?->numero_lot }}</a>
                        <div class="nt-cell-sub">{{ $indicateur->empreinteCarbone->lot?->produit?->nom }}</div>
                    @else
                        —
                    @endif
                </td>
                <td class="text-muted">
                    @if ($indicateur->etape)
                        <a href="{{ route('admin.etapes.show', $indicateur->etape) }}">{{ $indicateur->etape->typeLabel() }} · {{ $indicateur->etape->lieu }}</a>
                    @else
                        Aucune
                    @endif
                </td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.indicateurs.show', $indicateur)"
                        :edit="route('admin.indicateurs.edit', $indicateur)"
                        :delete="route('admin.indicateurs.destroy', $indicateur)"
                        confirm="Supprimer cet indicateur ?" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-speedometer2" title="Aucun indicateur saisi">
                Détaillez une empreinte carbone par poste d'impact (eau, énergie, transport, emballage).
                <x-slot:action>
                    <a href="{{ route('admin.indicateurs.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un indicateur
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
