@extends('layouts.admin')

@section('title', $laboratoire->nom)

@section('content')
    @php($nonConformes = $laboratoire->analyses->where('resultat', 'non_conforme')->count())

    <x-admin.page-header :title="$laboratoire->nom" module="Module 2 · Laboratoire"
                         :subtitle="$laboratoire->accreditation . ' · ' . $laboratoire->ville" :back="route('admin.laboratoires.index')">
        <x-slot:actions>
            <a href="{{ route('admin.laboratoires.edit', $laboratoire) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Fiche laboratoire</h2></div>
                <div class="card-body">
                    <div class="nt-role-row"><span class="text-muted">Ville</span><strong>{{ $laboratoire->ville }} ({{ $laboratoire->pays }})</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Accréditation</span><span class="nt-badge nt-badge-info">{{ $laboratoire->accreditation }}</span></div>
                    <div class="nt-role-row"><span class="text-muted">E-mail</span><a href="mailto:{{ $laboratoire->email }}">{{ $laboratoire->email }}</a></div>
                    <div class="nt-role-row"><span class="text-muted">Téléphone</span><strong>{{ $laboratoire->telephone }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Ajouté le</span><strong>{{ $laboratoire->created_at?->format('d/m/Y') }}</strong></div>
                </div>
            </div>

            <div class="row">
                <div class="col-4 mb-3">
                    <div class="card h-100"><div class="card-body text-center p-3">
                        <div class="h4 mb-0">{{ $laboratoire->analyses->count() }}</div>
                        <div class="text-muted small">Analyses</div>
                    </div></div>
                </div>
                <div class="col-4 mb-3">
                    <div class="card h-100"><div class="card-body text-center p-3">
                        <div class="h4 mb-0 {{ $nonConformes ? 'text-danger' : 'text-success' }}">{{ $nonConformes }}</div>
                        <div class="text-muted small">Non conformes</div>
                    </div></div>
                </div>
                <div class="col-4 mb-3">
                    <div class="card h-100"><div class="card-body text-center p-3">
                        <div class="h4 mb-0">{{ $nbLots }}</div>
                        <div class="text-muted small">Lots</div>
                    </div></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <x-admin.table-card :items="$laboratoire->analyses" title="Analyses réalisées par ce laboratoire">
                <x-slot:head>
                    <th scope="col">Numéro</th>
                    <th scope="col">Type</th>
                    <th scope="col">Lot</th>
                    <th scope="col">Prélèvement</th>
                    <th scope="col">Résultat</th>
                    <th scope="col" class="text-right">Actions</th>
                </x-slot:head>

                @foreach ($laboratoire->analyses as $analyse)
                    <tr>
                        <td class="nt-cell-title">{{ $analyse->numero }}</td>
                        <td><span class="nt-badge"><i class="bi {{ $analyse->icone() }}" aria-hidden="true"></i> {{ $analyse->typeLabel() }}</span></td>
                        <td>
                            @if ($analyse->lot)
                                <a href="{{ route('admin.lots.show', $analyse->lot) }}" class="nt-cell-title">{{ $analyse->lot->numero_lot }}</a>
                                <div class="nt-cell-sub">{{ $analyse->lot->produit?->nom }}</div>
                            @endif
                        </td>
                        <td class="text-muted">{{ $analyse->date_prelevement?->format('d/m/Y') }}</td>
                        <td>@include('pages.admin.analyses._resultat')</td>
                        <td class="text-right">
                            <x-admin.row-actions
                                :show="route('admin.analyses.show', $analyse)"
                                :edit="route('admin.analyses.edit', $analyse)"
                                :delete="route('admin.analyses.destroy', $analyse)"
                                :confirm="'Supprimer l\'analyse ' . $analyse->numero . ' ?'" />
                        </td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-clipboard2-pulse" title="Aucune analyse réalisée">
                        Enregistrez une première analyse de lot confiée à ce laboratoire.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>

            <a href="{{ route('admin.analyses.create', ['laboratoire' => $laboratoire->id]) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Enregistrer une analyse
            </a>
        </div>
    </div>
@endsection
