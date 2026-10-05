@extends('layouts.pro')

@section('title', 'Mes produits')

@section('pro_content')
    <div class="pro-toolbar">
        <form method="GET" action="{{ route('pro.produits.index') }}" class="d-flex gap-2" role="search">
            <label for="q" class="visually-hidden">Rechercher</label>
            <input class="form-control" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou code-barres…">
            <button class="btn btn-outline-primary" type="submit" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
        </form>
        <a href="{{ route('pro.produits.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nouveau produit</a>
    </div>

    @if ($produits->isEmpty())
        @if (request()->filled('q'))
            <x-front.empty-state icon="bi-search" title="Aucun produit ne correspond à votre recherche" :reset="route('pro.produits.index')">
                Essayez un autre nom ou code-barres.
            </x-front.empty-state>
        @else
            <x-front.empty-state icon="bi-box-seam" title="Vous n'avez encore aucun produit">
                Ajoutez votre premier produit : il apparaîtra dans le catalogue public avec son score et ses labels.
            </x-front.empty-state>
        @endif
    @else
        <div class="data-card">
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Produit</th>
                            <th scope="col">Catégorie</th>
                            <th scope="col">Score</th>
                            <th scope="col">Lots</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($produits as $produit)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($produit->image)
                                            <img src="{{ asset('storage/' . $produit->image) }}" alt="" class="thumb">
                                        @else
                                            <span class="thumb thumb-placeholder"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                                        @endif
                                        <div>
                                            <div class="cell-title">{{ $produit->nom }}</div>
                                            <div class="cell-sub"><i class="bi bi-upc me-1" aria-hidden="true"></i>{{ $produit->code_barres }} · {{ $produit->origine }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="label-chip">{{ $produit->categorie?->nom }}</span></td>
                                <td><x-front.score-badge :score="$produit->scoreGlobal()" taille="sm" /></td>
                                <td>{{ $produit->lots_count }}</td>
                                <td class="cell-actions">
                                    <a href="{{ route('front.produits.show', $produit) }}" class="btn btn-sm btn-outline-secondary" title="Voir la fiche publique"><i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Voir</span></a>
                                    @can('update', $produit)
                                        <a href="{{ route('pro.produits.edit', $produit) }}" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Modifier</span></a>
                                    @endcan
                                    @can('delete', $produit)
                                        <form method="POST" action="{{ route('pro.produits.destroy', $produit) }}" class="d-inline"
                                              onsubmit="return confirm('Supprimer le produit « {{ addslashes($produit->nom) }} » ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Supprimer</span></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-front.pagination :items="$produits" />
    @endif
@endsection
