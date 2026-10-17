@extends('layouts.front')

@section('title', 'Comparer des produits')

@push('scripts')
    @vite('resources/assets/front/js/module1.js')
@endpush

@section('content')
    <x-front.page-header
        eyebrow="Module 1 · Comparateur"
        title="Comparer des fiches produits"
        image="marche-fruits-legumes.webp"
        subtitle="Éco-score, CO₂ moyen, labels, lots tracés et conformité côte à côte." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">

            {{-- Lien vers le comparateur de Sahar (Module 4) --}}
            <div class="notice-panel mb-4">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>
                <div>
                    Vous cherchez une comparaison des <strong>indicateurs environnementaux</strong>
                    (eau, énergie, transport, emballage) ?
                    Consultez le <a href="{{ $lienSahar }}">comparateur d'empreintes environnementales</a>.
                </div>
            </div>

            @if ($produits->count() < 2)
                <x-front.empty-state icon="bi-bar-chart-steps" title="Sélectionnez au moins deux produits">
                    Ajoutez des produits à la comparaison depuis le catalogue en cliquant sur
                    « Comparer » sur chaque fiche.
                    <x-slot:action>
                        <a href="{{ route('front.produits.index') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-basket2 me-1" aria-hidden="true"></i>Aller au catalogue
                        </a>
                    </x-slot:action>
                </x-front.empty-state>

            @else

                {{-- ── URL partageable ──────────────────────────────────────── --}}
                @if ($urlPartage)
                    <div class="panel mb-4 d-flex align-items-center gap-3 flex-wrap">
                        <i class="bi bi-share text-success" aria-hidden="true"></i>
                        <span class="small text-muted flex-fill text-truncate">{{ $urlPartage }}</span>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $urlPartage }}').then(function(){this.textContent='✓ Copié';}.bind(this))">
                            <i class="bi bi-clipboard me-1" aria-hidden="true"></i>Copier le lien
                        </button>
                    </div>
                @endif

                {{-- ── Tableau comparatif ───────────────────────────────────── --}}
                <div class="data-card mb-4">
                    <div class="table-responsive">
                        <table class="table data-table align-middle text-center">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-start">Critère</th>
                                    @foreach ($produits as $produit)
                                        <th scope="col">
                                            @if ($produit->image)
                                                <img src="{{ asset('storage/' . $produit->image) }}"
                                                     alt="" class="thumb thumb-placeholder d-block mx-auto mb-1">
                                            @else
                                                <span class="thumb thumb-placeholder d-flex mx-auto mb-1">
                                                    <i class="bi bi-box-seam" aria-hidden="true"></i>
                                                </span>
                                            @endif
                                            <a href="{{ route('front.produits.show', $produit) }}"
                                               class="fw-600 text-decoration-none">
                                                {{ $produit->nom }}
                                            </a>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>

                                {{-- Éco-score --}}
                                <tr>
                                    <th scope="row" class="text-start">
                                        <i class="bi bi-globe-europe-africa me-1 text-success" aria-hidden="true"></i>
                                        Éco-score
                                    </th>
                                    @foreach ($produits as $produit)
                                        @php $d = $donnees[$produit->id] @endphp
                                        <td>
                                            <x-front.score-badge :score="$d['score']" taille="sm" />
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- CO₂ moyen --}}
                                @php
                                    $co2Values = $produits->map(fn($p) => $donnees[$p->id]['co2'])->filter();
                                    $minCo2    = $co2Values->min();
                                @endphp
                                <tr>
                                    <th scope="row" class="text-start">
                                        <i class="bi bi-cloud-haze2 me-1 text-success" aria-hidden="true"></i>
                                        CO₂ moyen <span class="cell-sub d-block fw-normal">kg CO₂e / unité</span>
                                    </th>
                                    @foreach ($produits as $produit)
                                        @php $co2 = $donnees[$produit->id]['co2'] @endphp
                                        <td class="{{ $co2 !== null && $co2 == $minCo2 ? 'm1-comparer-meilleur' : '' }}">
                                            {{ $co2 !== null ? number_format($co2, 2, ',', ' ') : '—' }}
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Labels valides --}}
                                @php
                                    $maxLabels = $produits->max(fn($p) => $donnees[$p->id]['certifications']);
                                @endphp
                                <tr>
                                    <th scope="row" class="text-start">
                                        <i class="bi bi-award me-1 text-success" aria-hidden="true"></i>
                                        Labels valides
                                    </th>
                                    @foreach ($produits as $produit)
                                        @php $nb = $donnees[$produit->id]['certifications'] @endphp
                                        <td class="{{ $nb > 0 && $nb == $maxLabels ? 'm1-comparer-meilleur' : '' }}">
                                            {{ $nb }}
                                            @if ($produit->certifications->isNotEmpty())
                                                <div class="d-flex flex-wrap justify-content-center gap-1 mt-1">
                                                    @foreach ($produit->certifications->take(3) as $cert)
                                                        <x-front.certification-badge :certification="$cert" />
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Lots tracés --}}
                                @php
                                    $maxLots = $produits->max(fn($p) => $donnees[$p->id]['nb_lots']);
                                @endphp
                                <tr>
                                    <th scope="row" class="text-start">
                                        <i class="bi bi-upc-scan me-1 text-success" aria-hidden="true"></i>
                                        Lots tracés
                                    </th>
                                    @foreach ($produits as $produit)
                                        @php $nb = $donnees[$produit->id]['nb_lots'] @endphp
                                        <td class="{{ $nb == $maxLots && $maxLots > 0 ? 'm1-comparer-meilleur' : '' }}">
                                            {{ $nb }}
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- % lots conformes --}}
                                @php
                                    $maxPct = $produits->max(fn($p) => $donnees[$p->id]['pct_conformes']);
                                @endphp
                                <tr>
                                    <th scope="row" class="text-start">
                                        <i class="bi bi-clipboard2-check me-1 text-success" aria-hidden="true"></i>
                                        Lots analysés conformes
                                    </th>
                                    @foreach ($produits as $produit)
                                        @php $pct = $donnees[$produit->id]['pct_conformes'] @endphp
                                        <td class="{{ $pct !== null && $pct == $maxPct ? 'm1-comparer-meilleur' : '' }}">
                                            {{ $pct !== null ? $pct . ' %' : '—' }}
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Nb ingrédients --}}
                                @php
                                    $minIng = $produits->map(fn($p) => $donnees[$p->id]['nb_ingredients'])->filter()->min();
                                @endphp
                                <tr>
                                    <th scope="row" class="text-start">
                                        <i class="bi bi-list-ul me-1 text-success" aria-hidden="true"></i>
                                        Nb d'ingrédients
                                    </th>
                                    @foreach ($produits as $produit)
                                        @php $nb = $donnees[$produit->id]['nb_ingredients'] @endphp
                                        <td class="{{ $nb !== null && $minIng !== null && $nb == $minIng ? 'm1-comparer-meilleur' : '' }}">
                                            {{ $nb ?? '—' }}
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Catégorie --}}
                                <tr>
                                    <th scope="row" class="text-start">
                                        <i class="bi bi-tags me-1 text-success" aria-hidden="true"></i>
                                        Catégorie
                                    </th>
                                    @foreach ($produits as $produit)
                                        <td>{{ $donnees[$produit->id]['categorie'] ?? '—' }}</td>
                                    @endforeach
                                </tr>

                                {{-- Origine --}}
                                <tr>
                                    <th scope="row" class="text-start">
                                        <i class="bi bi-geo-alt me-1 text-success" aria-hidden="true"></i>
                                        Origine
                                    </th>
                                    @foreach ($produits as $produit)
                                        <td>{{ $donnees[$produit->id]['origine'] ?? '—' }}</td>
                                    @endforeach
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Verdict ──────────────────────────────────────────────── --}}
                @php
                    /* Le plus écologique = CO₂ moyen le plus faible (si dispo),
                       sinon meilleur score alphabétique. */
                    $scores = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
                    $meilleur = $produits->sortBy(function ($p) use ($donnees, $scores) {
                        $co2   = $donnees[$p->id]['co2'] ?? 999;
                        $score = $scores[$donnees[$p->id]['score'] ?? 'E'] ?? 5;
                        return [$co2, $score];
                    })->first();
                @endphp
                @if ($meilleur && $co2Values->isNotEmpty())
                    <div class="m1-verdict" role="status">
                        <i class="bi bi-trophy-fill" aria-hidden="true"></i>
                        <div>
                            <strong>{{ $meilleur->nom }}</strong> est le plus écologique de cette sélection
                            avec un CO₂ moyen de
                            <strong>{{ number_format($donnees[$meilleur->id]['co2'], 2, ',', ' ') }} kg CO₂e</strong>
                            par unité et un score
                            <x-front.score-badge :score="$donnees[$meilleur->id]['score']" taille="sm" />.
                        </div>
                    </div>
                @endif

                {{-- ── Bouton vider + retour catalogue ──────────────────────── --}}
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ route('front.produits.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-basket2 me-1" aria-hidden="true"></i>Retour au catalogue
                    </a>
                    <form method="POST" action="{{ route('front.produits.comparaison.vider') }}" class="m1-form-vider">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1" aria-hidden="true"></i>Vider la sélection
                        </button>
                    </form>
                </div>

            @endif

        </div>
    </section>

    {{-- Barre flottante (aussi présente ici pour cohérence) --}}
    @include('pages.front.produits._barre_comparaison')

@endsection
