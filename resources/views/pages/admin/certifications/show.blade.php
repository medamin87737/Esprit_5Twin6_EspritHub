@extends('layouts.admin')

@section('title', $certification->numero)

@section('content')
    <x-admin.page-header :title="$certification->numero" module="Module 5 · Certification"
                         :subtitle="$certification->typeLabel() . ' · ' . $certification->produit?->nom" :back="route('admin.certifications.index')">
        <x-slot:actions>
            <a href="{{ route('front.certifications.index', ['numero' => $certification->numero]) }}" class="btn btn-light" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right mr-1" aria-hidden="true"></i> Vue publique
            </a>
            <a href="{{ route('admin.certifications.edit', $certification) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Label</h2></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="nt-thumb nt-thumb-placeholder mr-3" style="width: 3rem; height: 3rem; font-size: 1.4rem;">
                            <i class="bi {{ $certification->icone() }}" aria-hidden="true"></i>
                        </span>
                        <div>
                            <div class="h5 mb-1">{{ $certification->typeLabel() }}</div>
                            @include('pages.admin.certifications._statut')
                        </div>
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Numéro</span><strong>{{ $certification->numero }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Obtenue le</span><strong>{{ $certification->date_obtention?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Expire le</span><strong>{{ $certification->date_expiration?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row">
                        <span class="text-muted">Validité</span>
                        @if ($certification->estValide())
                            <span class="text-success"><i class="bi bi-patch-check-fill mr-1" aria-hidden="true"></i>{{ $certification->joursRestants() }} jour(s) restant(s)</span>
                        @else
                            <span class="text-danger"><i class="bi bi-x-octagon-fill mr-1" aria-hidden="true"></i>Non valide</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="nt-card-title">Organisme certificateur</h2></div>
                        <div class="card-body">
                            @if ($certification->organisme)
                                <div class="nt-role-row">
                                    <span class="text-muted">Nom</span>
                                    <a href="{{ route('admin.organismes.show', $certification->organisme) }}" class="nt-badge">{{ $certification->organisme->nom }}</a>
                                </div>
                                <div class="nt-role-row"><span class="text-muted">Pays</span><strong>{{ $certification->organisme->pays }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Accréditation</span><strong>{{ $certification->organisme->accreditation }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="nt-card-title">Produit certifié</h2></div>
                        <div class="card-body">
                            @if ($certification->produit)
                                <div class="nt-role-row">
                                    <span class="text-muted">Produit</span>
                                    <a href="{{ route('admin.produits.show', $certification->produit) }}" class="nt-badge">{{ $certification->produit->nom }}</a>
                                </div>
                                <div class="nt-role-row"><span class="text-muted">Catégorie</span><strong>{{ $certification->produit->categorie?->nom ?? '—' }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Origine</span><strong>{{ $certification->produit->origine }}</strong></div>
                                <div class="nt-role-row"><span class="text-muted">Fournisseur</span><strong>{{ $certification->produit->fournisseur?->name ?? 'Administration' }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <x-admin.table-card :items="$autresLabels" title="Autres labels de ce produit">
                <x-slot:head>
                    <th scope="col">Numéro</th>
                    <th scope="col">Label</th>
                    <th scope="col">Organisme</th>
                    <th scope="col">Statut</th>
                    <th scope="col" class="text-right">Fiche</th>
                </x-slot:head>

                @foreach ($autresLabels as $autre)
                    <tr>
                        <td class="nt-cell-title">{{ $autre->numero }}</td>
                        <td><span class="nt-badge"><i class="bi {{ $autre->icone() }}" aria-hidden="true"></i> {{ $autre->typeLabel() }}</span></td>
                        <td class="text-muted">{{ $autre->organisme?->nom }}</td>
                        <td>@include('pages.admin.certifications._statut', ['certification' => $autre])</td>
                        <td class="text-right"><x-admin.row-actions :show="route('admin.certifications.show', $autre)" /></td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-award" title="Aucun autre label">
                        Ce produit ne porte que cette certification.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>
        </div>
    </div>
@endsection
