@extends('layouts.front')

@section('title', 'Catalogue des produits')

@section('content')
    @php
        $produits = $produits ?? collect();
        $categories = $categories ?? collect();
        $filtered = request()->filled('q') || request()->filled('categorie');
        $total = $produits instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $produits->total() : $produits->count();
    @endphp

    <x-front.page-header eyebrow="Catalogue" title="Les produits suivis par NutriTrace" image="marche-fruits-legumes.webp"
                         subtitle="Origine, composition et catégorie : retrouvez la fiche de chaque produit tracé sur la plateforme." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <form class="filter-bar" method="GET" action="{{ route('front.produits.index') }}" role="search">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label" for="q">Rechercher un produit</label>
                        <div class="field-icon">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input class="form-control" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Ex. yaourt, huile d'olive…">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="categorie">Catégorie</label>
                        <select class="form-select" id="categorie" name="categorie">
                            <option value="">Toutes les catégories</option>
                            @foreach ($categories as $categorie)
                                <option value="{{ $categorie->id }}" @selected((string) request('categorie') === (string) $categorie->id)>{{ $categorie->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">Filtrer</button>
                    </div>
                </div>
            </form>

            <div class="results-bar">
                <span><strong>{{ $total }}</strong> produit{{ $total > 1 ? 's' : '' }}</span>
                @if ($filtered)
                    <a href="{{ route('front.produits.index') }}" class="small"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Effacer les filtres</a>
                @endif
            </div>

            @if ($total === 0)
                @if ($filtered)
                    <x-front.empty-state icon="bi-search" title="Aucun produit ne correspond à votre recherche" :reset="route('front.produits.index')">
                        Essayez un autre mot-clé ou une autre catégorie.
                    </x-front.empty-state>
                @else
                    <x-front.empty-state icon="bi-basket2" title="Le catalogue est encore vide">
                        Les produits apparaîtront ici dès qu'ils auront été enregistrés par l'équipe NutriTrace.
                    </x-front.empty-state>
                @endif
            @else
                <div class="row g-4">
                    @foreach ($produits as $produit)
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <article class="product-card h-100">
                                <div class="product-card-media">
                                    @if ($produit->image)
                                        <img src="{{ asset('storage/' . $produit->image) }}" alt="{{ $produit->nom }}" loading="lazy">
                                    @else
                                        <span class="product-card-placeholder"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                                    @endif
                                    <span class="label-chip product-card-chip">{{ $produit->categorie?->nom }}</span>
                                </div>
                                <div class="product-card-body">
                                    <h2 class="product-card-title">{{ $produit->nom }}</h2>
                                    <p class="product-card-meta"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $produit->origine }}</p>
                                    @if (Route::has('front.produits.show'))
                                        <a href="{{ route('front.produits.show', $produit) }}" class="stretched-link product-card-link">Voir la fiche <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                    @endif
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>

                @if ($produits instanceof \Illuminate\Contracts\Pagination\Paginator && $produits->hasPages())
                    <div class="mt-5 d-flex justify-content-center">{{ $produits->withQueryString()->links('pagination::bootstrap-5') }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
