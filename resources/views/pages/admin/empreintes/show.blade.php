@extends('layouts.admin')

@section('title', 'Empreinte ' . $empreinte->lot?->numero_lot)

@section('content')
    @php
        $types = config('nutritrace.options.indicateur_types');
        $totaux = $empreinte->indicateurs->groupBy('type');
    @endphp

    <x-admin.page-header :title="'Empreinte · ' . $empreinte->lot?->numero_lot" module="Module 4 · Empreinte carbone"
                         :subtitle="$empreinte->lot?->produit?->nom" :back="route('admin.empreintes.index')">
        <x-slot:actions>
            <a href="{{ route('admin.empreintes.edit', $empreinte) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Bilan carbone</h2></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="nt-score nt-score-{{ strtolower($empreinte->score) }} mr-3" style="width: 3rem; height: 3rem; font-size: 1.5rem;">{{ $empreinte->score }}</span>
                        <div>
                            <div class="h4 mb-0">{{ number_format($empreinte->co2_total, 2, ',', ' ') }} kg</div>
                            <div class="text-muted small">CO₂e par kg de produit</div>
                        </div>
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Méthode</span><strong>{{ $empreinte->methode }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Calculée le</span><strong>{{ $empreinte->date_calcul?->format('d/m/Y') }}</strong></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="nt-card-title">Lot concerné</h2></div>
                <div class="card-body">
                    @if ($empreinte->lot)
                        <div class="nt-role-row">
                            <span class="text-muted">Lot</span>
                            <a href="{{ route('admin.lots.show', $empreinte->lot) }}" class="nt-badge">{{ $empreinte->lot->numero_lot }}</a>
                        </div>
                        <div class="nt-role-row">
                            <span class="text-muted">Produit</span>
                            @if ($empreinte->lot->produit)
                                <a href="{{ route('admin.produits.show', $empreinte->lot->produit) }}">{{ $empreinte->lot->produit->nom }}</a>
                            @endif
                        </div>
                        <div class="nt-role-row"><span class="text-muted">Catégorie</span><strong>{{ $empreinte->lot->produit?->categorie?->nom }}</strong></div>
                        <div class="nt-role-row"><span class="text-muted">Étapes tracées</span><strong>{{ $empreinte->lot->etapes->count() }}</strong></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            @if ($totaux->isNotEmpty())
                <div class="row">
                    @foreach ($totaux as $type => $groupe)
                        <div class="col-sm-6 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi {{ $groupe->first()->icone() }} text-primary" style="font-size: 1.5rem;" aria-hidden="true"></i>
                                    <div class="h5 mb-0 mt-2">{{ number_format($groupe->sum('valeur'), 0, ',', ' ') }} {{ $groupe->first()->unite }}</div>
                                    <div class="text-muted small">{{ $types[$type] ?? $type }} · {{ $groupe->count() }} mesure(s)</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <x-admin.table-card :items="$empreinte->indicateurs" title="Indicateurs de l'empreinte">
                <x-slot:head>
                    <th scope="col">Type</th>
                    <th scope="col" class="text-right">Valeur</th>
                    <th scope="col">Étape liée</th>
                    <th scope="col" class="text-right">Actions</th>
                </x-slot:head>

                @foreach ($empreinte->indicateurs as $indicateur)
                    <tr>
                        <td><span class="nt-badge"><i class="bi {{ $indicateur->icone() }}" aria-hidden="true"></i> {{ $indicateur->typeLabel() }}</span></td>
                        <td class="text-right font-weight-600">{{ number_format($indicateur->valeur, 2, ',', ' ') }} {{ $indicateur->unite }}</td>
                        <td class="text-muted">
                            @if ($indicateur->etape)
                                <a href="{{ route('admin.etapes.show', $indicateur->etape) }}">{{ $indicateur->etape->typeLabel() }} · {{ $indicateur->etape->lieu }}</a>
                            @else
                                Aucune (lot entier)
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
                    <x-admin.empty-state icon="bi-speedometer2" title="Aucun indicateur">
                        Détaillez cette empreinte par poste d'impact : eau, énergie, transport et emballage.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>

            <a href="{{ route('admin.indicateurs.create', ['empreinte' => $empreinte->id]) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un indicateur
            </a>
        </div>
    </div>
@endsection
