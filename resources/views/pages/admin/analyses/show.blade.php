@extends('layouts.admin')

@section('title', $analyse->numero)

@section('content')
    <x-admin.page-header :title="$analyse->numero" module="Module 2 · Analyse"
                         :subtitle="$analyse->typeLabel() . ' · lot ' . $analyse->lot?->numero_lot" :back="route('admin.analyses.index')">
        <x-slot:actions>
            @if ($analyse->lot)
                <a href="{{ route('front.lots.show', $analyse->lot) }}" class="btn btn-light" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right mr-1" aria-hidden="true"></i> Vue publique
                </a>
            @endif
            <a href="{{ route('admin.analyses.edit', $analyse) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Analyse</h2></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="nt-thumb nt-thumb-placeholder mr-3" style="width: 3rem; height: 3rem; font-size: 1.4rem;">
                            <i class="bi {{ $analyse->icone() }}" aria-hidden="true"></i>
                        </span>
                        <div>
                            <div class="h5 mb-1">{{ $analyse->typeLabel() }}</div>
                            @include('pages.admin.analyses._resultat')
                        </div>
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Numéro</span><strong>{{ $analyse->numero }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Prélevé le</span><strong>{{ $analyse->date_prelevement?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Résultat rendu le</span><strong>{{ $analyse->date_resultat?->format('d/m/Y') ?? '—' }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Déclarée par</span><strong>{{ $analyse->declarant?->name ?? '—' }}</strong></div>
                    <div class="nt-role-row">
                        <span class="text-muted">Rapport</span>
                        @if ($analyse->rapport)
                            <a href="{{ route('front.analyses.rapport', $analyse) }}"><i class="bi bi-file-earmark-pdf mr-1" aria-hidden="true"></i>Télécharger</a>
                        @else
                            <span class="text-muted">Aucun</span>
                        @endif
                    </div>
                    @if ($analyse->commentaire)
                        <p class="mt-3 mb-0 small {{ $analyse->resultat === 'non_conforme' ? 'text-danger' : 'text-muted' }}">{{ $analyse->commentaire }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="nt-card-title">Laboratoire</h2></div>
                        <div class="card-body">
                            @if ($analyse->laboratoire)
                                <div class="nt-role-row">
                                    <span class="text-muted">Nom</span>
                                    <a href="{{ route('admin.laboratoires.show', $analyse->laboratoire) }}" class="nt-badge">{{ $analyse->laboratoire->nom }}</a>
                                </div>
                                <div class="nt-role-row"><span class="text-muted">Ville</span><strong>{{ $analyse->laboratoire->ville }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Accréditation</span><strong>{{ $analyse->laboratoire->accreditation }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="nt-card-title">Lot analysé</h2></div>
                        <div class="card-body">
                            @if ($analyse->lot)
                                <div class="nt-role-row">
                                    <span class="text-muted">Lot</span>
                                    <a href="{{ route('admin.lots.show', $analyse->lot) }}" class="nt-badge">{{ $analyse->lot->numero_lot }}</a>
                                </div>
                                <div class="nt-role-row"><span class="text-muted">Produit</span><strong>{{ $analyse->lot->produit?->nom ?? '—' }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Production</span><strong>{{ $analyse->lot->date_production?->format('d/m/Y') }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Détenu par</span><strong>{{ $analyse->lot->acteurCourant?->nom ?? '—' }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <x-admin.table-card :items="$autresAnalyses" title="Autres analyses de ce lot">
                <x-slot:head>
                    <th scope="col">Numéro</th>
                    <th scope="col">Type</th>
                    <th scope="col">Laboratoire</th>
                    <th scope="col">Résultat</th>
                    <th scope="col" class="text-right">Fiche</th>
                </x-slot:head>

                @foreach ($autresAnalyses as $autre)
                    <tr>
                        <td class="nt-cell-title">{{ $autre->numero }}</td>
                        <td><span class="nt-badge"><i class="bi {{ $autre->icone() }}" aria-hidden="true"></i> {{ $autre->typeLabel() }}</span></td>
                        <td class="text-muted">{{ $autre->laboratoire?->nom }}</td>
                        <td>@include('pages.admin.analyses._resultat', ['analyse' => $autre])</td>
                        <td class="text-right"><x-admin.row-actions :show="route('admin.analyses.show', $autre)" /></td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-clipboard2-pulse" title="Aucune autre analyse">
                        Ce lot n'a fait l'objet que de cette analyse.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>
        </div>
    </div>
@endsection
