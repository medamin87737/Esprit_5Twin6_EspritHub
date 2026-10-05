@extends('layouts.admin')

@section('title', $organisme->nom)

@section('content')
    @php($valides = $organisme->certifications->filter->estValide()->count())

    <x-admin.page-header :title="$organisme->nom" module="Module 5 · Organisme certificateur"
                         :subtitle="$organisme->accreditation . ' · ' . $organisme->pays" :back="route('admin.organismes.index')">
        <x-slot:actions>
            <a href="{{ route('admin.organismes.edit', $organisme) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Fiche organisme</h2></div>
                <div class="card-body">
                    <div class="nt-role-row"><span class="text-muted">Pays</span><strong>{{ $organisme->pays }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Accréditation</span><span class="nt-badge nt-badge-info">{{ $organisme->accreditation }}</span></div>
                    <div class="nt-role-row">
                        <span class="text-muted">Site web</span>
                        <a href="{{ $organisme->site_web }}" target="_blank" rel="noopener">
                            {{ parse_url($organisme->site_web, PHP_URL_HOST) ?? $organisme->site_web }} <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Ajouté le</span><strong>{{ $organisme->created_at?->format('d/m/Y') }}</strong></div>
                </div>
            </div>

            <div class="row">
                <div class="col-4 mb-3">
                    <div class="card h-100"><div class="card-body text-center p-3">
                        <div class="h4 mb-0">{{ $organisme->certifications->count() }}</div>
                        <div class="text-muted small">Certifications</div>
                    </div></div>
                </div>
                <div class="col-4 mb-3">
                    <div class="card h-100"><div class="card-body text-center p-3">
                        <div class="h4 mb-0 text-success">{{ $valides }}</div>
                        <div class="text-muted small">Valides</div>
                    </div></div>
                </div>
                <div class="col-4 mb-3">
                    <div class="card h-100"><div class="card-body text-center p-3">
                        <div class="h4 mb-0">{{ $nbProduits }}</div>
                        <div class="text-muted small">Produits</div>
                    </div></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <x-admin.table-card :items="$organisme->certifications" title="Certifications délivrées par cet organisme">
                <x-slot:head>
                    <th scope="col">Numéro</th>
                    <th scope="col">Label</th>
                    <th scope="col">Produit</th>
                    <th scope="col">Expiration</th>
                    <th scope="col">Statut</th>
                    <th scope="col" class="text-right">Actions</th>
                </x-slot:head>

                @foreach ($organisme->certifications as $certification)
                    <tr>
                        <td class="nt-cell-title">{{ $certification->numero }}</td>
                        <td><span class="nt-badge"><i class="bi {{ $certification->icone() }}" aria-hidden="true"></i> {{ $certification->typeLabel() }}</span></td>
                        <td>
                            @if ($certification->produit)
                                <a href="{{ route('admin.produits.show', $certification->produit) }}" class="nt-cell-title">{{ $certification->produit->nom }}</a>
                                <div class="nt-cell-sub">{{ $certification->produit->categorie?->nom }}</div>
                            @endif
                        </td>
                        <td class="text-muted">{{ $certification->date_expiration?->format('d/m/Y') }}</td>
                        <td>@include('pages.admin.certifications._statut')</td>
                        <td class="text-right">
                            <x-admin.row-actions
                                :show="route('admin.certifications.show', $certification)"
                                :edit="route('admin.certifications.edit', $certification)"
                                :delete="route('admin.certifications.destroy', $certification)"
                                :confirm="'Supprimer la certification ' . $certification->numero . ' ?'" />
                        </td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-award" title="Aucune certification délivrée">
                        Attribuez un premier label à un produit au nom de cet organisme.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>

            <a href="{{ route('admin.certifications.create', ['organisme' => $organisme->id]) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Délivrer une certification
            </a>
        </div>
    </div>
@endsection
