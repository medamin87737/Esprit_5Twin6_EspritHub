@extends('layouts.admin')

@section('title', $categorie->nom)

@section('content')
    <x-admin.page-header :title="$categorie->nom" module="Module 1 · Catégorie"
                         :subtitle="$categorie->description" :back="route('admin.categories.index')">
        <x-slot:actions>
            <a href="{{ route('admin.categories.edit', $categorie) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-header"><h2 class="nt-card-title">Informations</h2></div>
                <div class="card-body">
                    <div class="nt-role-row"><span class="text-muted">Type</span><span class="nt-badge">{{ $categorie->typeLabel() }}</span></div>
                    <div class="nt-role-row"><span class="text-muted">Produits</span><strong>{{ $categorie->produits->count() }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Créée le</span><strong>{{ $categorie->created_at?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Modifiée le</span><strong>{{ $categorie->updated_at?->format('d/m/Y') }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <x-admin.table-card :items="$categorie->produits" title="Produits de cette catégorie">
                <x-slot:head>
                    <th scope="col">Produit</th>
                    <th scope="col">Code-barres</th>
                    <th scope="col">Origine</th>
                    <th scope="col" class="text-right">Actions</th>
                </x-slot:head>

                @foreach ($categorie->produits as $produit)
                    <tr>
                        <td class="nt-cell-title">{{ $produit->nom }}</td>
                        <td class="text-muted">{{ $produit->code_barres }}</td>
                        <td>{{ $produit->origine }}</td>
                        <td class="text-right">
                            <x-admin.row-actions :show="route('admin.produits.show', $produit)" :edit="route('admin.produits.edit', $produit)" />
                        </td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-box-seam" title="Aucun produit dans cette catégorie">
                        Les produits rattachés à « {{ $categorie->nom }} » apparaîtront ici.
                        <x-slot:action>
                            <a href="{{ route('admin.produits.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un produit
                            </a>
                        </x-slot:action>
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>
        </div>
    </div>
@endsection
