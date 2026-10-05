@extends('layouts.front')

@section('title', 'Catalogue des produits')

@section('content')
    @php
        $filtre = collect(request()->only(['q', 'categorie', 'score', 'label']))->filter()->isNotEmpty();
        $total = $produits->total();
    @endphp

    <x-front.page-header eyebrow="Catalogue" title="Les produits suivis par NutriTrace" image="marche-fruits-legumes.webp"
                         subtitle="Origine, score environnemental et labels vérifiés : retrouvez la fiche de chaque produit tracé." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <form class="filter-bar" method="GET" action="{{ route('front.produits.index') }}" role="search">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label" for="q">Nom ou code-barres</label>
                        <div class="field-icon">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input class="form-control" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Ex. yaourt, 6191234567890">
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="categorie">Catégorie</label>
                        <select class="form-select" id="categorie" name="categorie">
                            <option value="">Toutes</option>
                            @foreach ($categories as $categorie)
                                <option value="{{ $categorie->id }}" @selected((string) request('categorie') === (string) $categorie->id)>{{ $categorie->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="score">Score</label>
                        <select class="form-select" id="score" name="score">
                            <option value="">Tous</option>
                            @foreach (config('nutritrace.options.scores') as $score => $seuil)
                                <option value="{{ $score }}" @selected(request('score') === $score)>{{ $score }} · {{ $seuil }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="label">Certification</label>
                        <select class="form-select" id="label" name="label">
                            <option value="">Toutes</option>
                            @foreach (config('nutritrace.options.certification_types') as $valeur => $libelle)
                                <option value="{{ $valeur }}" @selected(request('label') === $valeur)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-1 d-grid">
                        <button class="btn btn-primary" type="submit" aria-label="Filtrer"><i class="bi bi-funnel" aria-hidden="true"></i></button>
                    </div>
                </div>
            </form>

            <div class="results-bar">
                <span><strong>{{ $total }}</strong> produit{{ $total > 1 ? 's' : '' }}</span>
                @if ($filtre)
                    <a href="{{ route('front.produits.index') }}" class="small"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Effacer les filtres</a>
                @endif
            </div>

            @if ($total === 0)
                @if ($filtre)
                    <x-front.empty-state icon="bi-search" title="Aucun produit ne correspond à votre recherche" :reset="route('front.produits.index')">
                        Essayez un autre mot-clé, une autre catégorie ou un autre score.
                    </x-front.empty-state>
                @else
                    <x-front.empty-state icon="bi-basket2" title="Le catalogue est encore vide">
                        Les produits apparaîtront ici dès leur enregistrement par les producteurs et transformateurs.
                    </x-front.empty-state>
                @endif
            @else
                <div class="row g-4">
                    @foreach ($produits as $produit)
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <x-front.produit-card :produit="$produit" />
                        </div>
                    @endforeach
                </div>

                <x-front.pagination :items="$produits" />
            @endif
        </div>
    </section>
@endsection
