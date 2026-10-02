@extends('layouts.admin')

@section('title', 'Empreintes carbone')

@section('content')
    @php($empreintes = $empreintes ?? collect())

    <x-admin.page-header title="Empreintes carbone" module="Module 4 · Empreinte environnementale"
                         subtitle="Le bilan CO₂ de chaque lot et son éco-score, calculé automatiquement.">
        <x-slot:actions>
            <a href="{{ route('admin.empreintes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouvelle empreinte
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$empreintes" title="Liste des empreintes" search-placeholder="Numéro de lot ou méthode…" :filter-keys="['q', 'score']">
        <x-slot:filters>
            <label for="filter-score" class="sr-only">Score</label>
            <select id="filter-score" name="score" class="custom-select">
                <option value="">Tous les scores</option>
                @foreach (array_keys(config('nutritrace.options.scores')) as $score)
                    <option value="{{ $score }}" @selected(request('score') === $score)>Score {{ $score }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Lot</th>
            <th scope="col" class="text-right">CO₂ total</th>
            <th scope="col" class="text-center">Score</th>
            <th scope="col">Méthode</th>
            <th scope="col">Calculée le</th>
            <th scope="col" class="text-center">Indicateurs</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($empreintes as $empreinte)
            <tr>
                <td>
                    <div class="nt-cell-title">{{ $empreinte->lot?->numero_lot ?? '—' }}</div>
                    <div class="nt-cell-sub">{{ $empreinte->lot?->produit?->nom }}</div>
                </td>
                <td class="text-right font-weight-600">{{ number_format($empreinte->co2_total, 2, ',', ' ') }} kg</td>
                <td class="text-center"><span class="nt-score nt-score-{{ strtolower($empreinte->score) }}">{{ $empreinte->score }}</span></td>
                <td class="text-muted">{{ $empreinte->methode }}</td>
                <td class="text-muted">{{ $empreinte->date_calcul?->format('d/m/Y') }}</td>
                <td class="text-center font-weight-600">{{ $empreinte->indicateurs_count ?? $empreinte->indicateurs->count() }}</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.empreintes.show', $empreinte)"
                        :edit="route('admin.empreintes.edit', $empreinte)"
                        :delete="route('admin.empreintes.destroy', $empreinte)"
                        confirm="Supprimer cette empreinte et ses indicateurs ?" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-cloud-haze2" title="Aucune empreinte calculée">
                Saisissez le CO₂ total d'un lot : l'éco-score de A à E sera attribué automatiquement.
                <x-slot:action>
                    <a href="{{ route('admin.empreintes.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Calculer une empreinte
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
