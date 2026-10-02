@extends('layouts.admin')

@section('title', 'Produits')

@section('content')
    @php
        $produits = $produits ?? collect();
        $categories = $categories ?? collect();
    @endphp

    <x-admin.page-header title="Produits" module="Module 1 · Produits & Catégories"
                         subtitle="Le catalogue des produits suivis, avec leur catégorie et leur origine.">
        <x-slot:actions>
            <a href="{{ route('admin.produits.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouveau produit
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$produits" title="Liste des produits" search-placeholder="Nom ou code-barres…" :filter-keys="['q', 'categorie']">
        <x-slot:filters>
            <label for="filter-categorie" class="sr-only">Catégorie</label>
            <select id="filter-categorie" name="categorie" class="custom-select">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected((string) request('categorie') === (string) $categorie->id)>{{ $categorie->nom }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Produit</th>
            <th scope="col">Catégorie</th>
            <th scope="col">Origine</th>
            <th scope="col">Ajouté le</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($produits as $produit)
            <tr>
                <td>
                    <div class="d-flex align-items-center" style="gap: 0.75rem;">
                        @if ($produit->image)
                            <img src="{{ asset('storage/' . $produit->image) }}" alt="" class="nt-thumb">
                        @else
                            <span class="nt-thumb nt-thumb-placeholder"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                        @endif
                        <div>
                            <div class="nt-cell-title">{{ $produit->nom }}</div>
                            <div class="nt-cell-sub"><i class="bi bi-upc mr-1" aria-hidden="true"></i>{{ $produit->code_barres }}</div>
                        </div>
                    </div>
                </td>
                <td><span class="nt-badge">{{ $produit->categorie?->nom ?? '—' }}</span></td>
                <td>{{ $produit->origine }}</td>
                <td class="text-muted">{{ $produit->created_at?->format('d/m/Y') }}</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.produits.show', $produit)"
                        :edit="route('admin.produits.edit', $produit)"
                        :delete="route('admin.produits.destroy', $produit)"
                        :confirm="'Supprimer le produit « ' . $produit->nom . ' » ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-box-seam" title="Aucun produit enregistré">
                Ajoutez un premier produit et rattachez-le à une catégorie. Il apparaîtra aussi dans le catalogue public.
                <x-slot:action>
                    <a href="{{ route('admin.produits.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un produit
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
