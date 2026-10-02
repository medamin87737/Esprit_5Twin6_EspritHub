@extends('layouts.admin')

@section('title', $produit->nom)

@section('content')
    <x-admin.page-header :title="$produit->nom" module="Module 1 · Produit"
                         :subtitle="$produit->description" :back="route('admin.produits.index')">
        <x-slot:actions>
            <a href="{{ route('admin.produits.edit', $produit) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    @if ($produit->image)
                        <img src="{{ asset('storage/' . $produit->image) }}" alt="{{ $produit->nom }}" class="img-fluid rounded">
                    @else
                        <span class="nt-thumb nt-thumb-placeholder mx-auto" style="width: 6rem; height: 6rem; font-size: 2rem;">
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                        </span>
                        <p class="text-muted small mt-3 mb-0">Aucune image</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Fiche produit</h2></div>
                <div class="card-body">
                    <div class="nt-role-row">
                        <span class="text-muted">Catégorie</span>
                        @if ($produit->categorie)
                            <a href="{{ route('admin.categories.show', $produit->categorie) }}" class="nt-badge">{{ $produit->categorie->nom }}</a>
                        @endif
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Code-barres</span><strong>{{ $produit->code_barres }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Origine</span><strong>{{ $produit->origine }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Ajouté le</span><strong>{{ $produit->created_at?->format('d/m/Y') }}</strong></div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Composition</h2></div>
                <div class="card-body">
                    <p class="mb-0 {{ $produit->composition ? '' : 'text-muted' }}">{{ $produit->composition ?: 'Non renseignée.' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
