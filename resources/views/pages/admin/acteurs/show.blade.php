@extends('layouts.admin')

@section('title', $acteur->nom)

@section('content')
    <x-admin.page-header :title="$acteur->nom" module="Module 2 · Acteur"
                         :subtitle="$acteur->typeActeur?->role_chaine" :back="route('admin.acteurs.index')">
        <x-slot:actions>
            <a href="{{ route('admin.acteurs.edit', $acteur) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Coordonnées</h2></div>
                <div class="card-body">
                    <div class="nt-role-row">
                        <span class="text-muted">Type</span>
                        @if ($acteur->typeActeur)
                            <a href="{{ route('admin.type-acteurs.show', $acteur->typeActeur) }}" class="nt-badge">{{ $acteur->typeActeur->libelle }}</a>
                        @endif
                    </div>
                    <div class="nt-role-row"><span class="text-muted">E-mail</span><a href="mailto:{{ $acteur->email }}">{{ $acteur->email }}</a></div>
                    <div class="nt-role-row"><span class="text-muted">Téléphone</span><strong>{{ $acteur->telephone }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Inscription</span><strong>{{ $acteur->date_inscription?->format('d/m/Y') }}</strong></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="nt-card-title">Localisation</h2></div>
                <div class="card-body">
                    <p class="mb-1">{{ $acteur->adresse }}</p>
                    <p class="text-muted mb-0">{{ $acteur->pays }}</p>
                    @if ($acteur->hasCoordinates())
                        <a href="https://www.openstreetmap.org/?mlat={{ $acteur->latitude }}&mlon={{ $acteur->longitude }}#map=13/{{ $acteur->latitude }}/{{ $acteur->longitude }}"
                           target="_blank" rel="noopener" class="btn btn-light btn-sm mt-3">
                            <i class="bi bi-map mr-1" aria-hidden="true"></i> Voir sur la carte
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <x-admin.table-card :items="$acteur->produits" title="Produits pris en charge">
                <x-slot:head>
                    <th scope="col">Produit</th>
                    <th scope="col">Catégorie</th>
                    <th scope="col">Origine</th>
                    <th scope="col" class="text-right">Fiche</th>
                </x-slot:head>

                @foreach ($acteur->produits as $produit)
                    <tr>
                        <td class="nt-cell-title">{{ $produit->nom }}</td>
                        <td><span class="nt-badge">{{ $produit->categorie?->nom }}</span></td>
                        <td>{{ $produit->origine }}</td>
                        <td class="text-right"><x-admin.row-actions :show="route('admin.produits.show', $produit)" /></td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-box-seam" title="Aucun produit associé">
                        Modifiez la fiche de cet acteur pour lui associer les produits qu'il prend en charge.
                        <x-slot:action>
                            <a href="{{ route('admin.acteurs.edit', $acteur) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-link-45deg mr-1" aria-hidden="true"></i> Associer des produits
                            </a>
                        </x-slot:action>
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>
        </div>
    </div>
@endsection
