@extends('layouts.admin')

@section('title', $espace === 'fournisseur' ? 'Mes produits' : 'Produits')

@section('content')
    @php
        $produits = $produits ?? collect();
        $categories = $categories ?? collect();
        $fournisseurs = $fournisseurs ?? collect();
        $estAdmin = $espace === 'admin';
    @endphp

    <x-admin.page-header :title="$estAdmin ? 'Produits' : 'Mes produits'" :module="$moduleLabel"
                         :subtitle="$estAdmin
                             ? 'Le catalogue des produits suivis, avec leur catégorie, leur fournisseur et leur origine.'
                             : 'Les produits que vous proposez : ajoutez, modifiez ou retirez-les du catalogue.'">
        <x-slot:actions>
            <a href="{{ route($espace . '.produits.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouveau produit
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$produits" :title="$estAdmin ? 'Liste des produits' : 'Mes produits'" search-placeholder="Nom ou code-barres…"
                        :filter-keys="['q', 'categorie', 'fournisseur']">
        <x-slot:filters>
            <label for="filter-categorie" class="sr-only">Catégorie</label>
            <select id="filter-categorie" name="categorie" class="custom-select">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected((string) request('categorie') === (string) $categorie->id)>{{ $categorie->nom }}</option>
                @endforeach
            </select>
            @if ($estAdmin)
                <label for="filter-fournisseur" class="sr-only">Fournisseur</label>
                <select id="filter-fournisseur" name="fournisseur" class="custom-select">
                    <option value="">Tous les fournisseurs</option>
                    @foreach ($fournisseurs as $fournisseur)
                        <option value="{{ $fournisseur->id }}" @selected((string) request('fournisseur') === (string) $fournisseur->id)>{{ $fournisseur->name }}</option>
                    @endforeach
                </select>
            @endif
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Produit</th>
            <th scope="col">Catégorie</th>
            @if ($estAdmin)
                <th scope="col">Fournisseur</th>
            @endif
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
                @if ($estAdmin)
                    <td>
                        @if ($produit->fournisseur)
                            <i class="bi bi-person-badge mr-1 text-muted" aria-hidden="true"></i>{{ $produit->fournisseur->name }}
                        @else
                            <span class="text-muted">Administration</span>
                        @endif
                    </td>
                @endif
                <td>{{ $produit->origine }}</td>
                <td class="text-muted">{{ $produit->created_at?->format('d/m/Y') }}</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route($espace . '.produits.show', $produit)"
                        :edit="route($espace . '.produits.edit', $produit)"
                        :delete="route($espace . '.produits.destroy', $produit)"
                        :confirm="'Supprimer le produit « ' . $produit->nom . ' » ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-box-seam" :title="$estAdmin ? 'Aucun produit enregistré' : 'Vous ne proposez encore aucun produit'">
                Ajoutez un premier produit et rattachez-le à une catégorie. Il apparaîtra aussi dans le catalogue public.
                <x-slot:action>
                    <a href="{{ route($espace . '.produits.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un produit
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
