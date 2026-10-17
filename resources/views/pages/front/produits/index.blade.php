@extends('layouts.front')

@section('title', 'Catalogue des produits')

@push('styles')
    {{-- module1.css est bundlé dans module1.js (import CSS via Vite) --}}
@endpush

@section('content')
    <x-front.page-header
        eyebrow="Catalogue"
        title="Les produits suivis par NutriTrace"
        image="marche-fruits-legumes.webp"
        subtitle="Origine, score environnemental et labels vérifiés : retrouvez la fiche de chaque produit tracé." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">

            {{-- ── Formulaire de filtres ────────────────────────────────────── --}}
            <form class="m1-filter-bar filter-bar"
                  method="GET"
                  action="{{ route('front.produits.index') }}"
                  id="m1-form-filtres"
                  role="search"
                  aria-label="Filtrer le catalogue">

                <div class="row g-3 align-items-end">

                    {{-- Recherche textuelle --}}
                    <div class="col-lg-4">
                        <label class="form-label" for="q">Nom, code-barres, origine ou ingrédient</label>
                        <div class="field-icon">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input class="form-control" id="q" type="search" name="q"
                                   value="{{ request('q') }}"
                                   placeholder="Ex. yaourt, Béja, 6191234567890"
                                   autocomplete="off">
                        </div>
                    </div>

                    {{-- Catégorie --}}
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="categorie">Catégorie</label>
                        <select class="form-select" id="categorie" name="categorie">
                            <option value="">Toutes</option>
                            @foreach ($categories as $categorie)
                                <option value="{{ $categorie->id }}"
                                    @selected((string) request('categorie') === (string) $categorie->id)>
                                    {{ $categorie->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Famille (type de catégorie) --}}
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="famille">Famille</label>
                        <select class="form-select" id="famille" name="famille">
                            <option value="">Toutes</option>
                            @foreach (config('nutritrace.options.categorie_types') as $valeur => $libelle)
                                <option value="{{ $valeur }}" @selected(request('famille') === $valeur)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Éco-score « X ou mieux » --}}
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="score">Éco-score</label>
                        <select class="form-select" id="score" name="score">
                            <option value="">Tous</option>
                            @foreach (config('nutritrace.options.scores') as $score => $seuil)
                                <option value="{{ $score }}" @selected(request('score') === $score)>{{ $score }} ou mieux</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Origine --}}
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="origine">Origine</label>
                        <input class="form-control" id="origine" type="text" name="origine"
                               value="{{ request('origine') }}" placeholder="Ex. Béja">
                    </div>

                    {{-- Bouton filtrer --}}
                    <div class="col-sm-6 col-lg-1 d-grid">
                        <button class="btn btn-primary" type="submit" aria-label="Appliquer les filtres">
                            <i class="bi bi-funnel" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                {{-- Ligne secondaire : labels multiples + certifiés + tri --}}
                <div class="row g-3 align-items-end mt-1">
                    <div class="col-sm-12 col-lg-5">
                        <fieldset>
                            <legend class="form-label">Labels (plusieurs possibles)</legend>
                            <div class="m1-labels-checkboxes d-flex flex-wrap gap-2">
                                @foreach (config('nutritrace.options.certification_types') as $valeur => $libelle)
                                    <label class="m1-label-check form-check-label">
                                        <input type="checkbox"
                                               name="labels[]"
                                               value="{{ $valeur }}"
                                               class="form-check-input me-1"
                                               @checked(in_array($valeur, request()->input('labels', [])))>
                                        {{ $libelle }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="certifies"
                                   name="certifies" value="1" @checked(request('certifies')=='1')>
                            <label class="form-check-label" for="certifies">
                                Produits certifiés uniquement
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="tri">Trier par</label>
                        <select class="form-select" id="tri" name="tri">
                            <option value="recents" @selected((request('tri', 'recents') === 'recents'))>Plus récents</option>
                            <option value="eco_score" @selected(request('tri') === 'eco_score')>Meilleur éco-score</option>
                            <option value="labels" @selected(request('tri') === 'labels')>Nombre de labels</option>
                            <option value="nom" @selected(request('tri') === 'nom')>Nom A → Z</option>
                        </select>
                    </div>

                    <div class="col-lg-1 d-grid">
                        <a href="{{ route('front.produits.index') }}"
                           class="btn btn-outline-secondary"
                           aria-label="Réinitialiser les filtres"
                           title="Réinitialiser">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </form>

            {{-- ── Zone de résultats (remplacée par AJAX) ─────────────────────── --}}
            <div id="m1-zone-resultats">
                @include('pages.front.produits._resultats', [
                    'produits'      => $produits,
                    'filtres'       => $filtres,
                    'repartition'   => $repartition,
                    'filtresActifs' => $filtresActifs,
                ])
            </div>

        </div>
    </section>

    {{-- Barre flottante comparaison (injectée par JS depuis session) --}}
    @include('pages.front.produits._barre_comparaison')

@endsection

@push('scripts')
    @vite('resources/assets/front/js/module1.js')
@endpush
