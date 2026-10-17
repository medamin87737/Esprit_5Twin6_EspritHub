{{--
    Partial _resultats.blade.php — Module 1 (Ghada)
    Rendu par CatalogueController en mode fragment AJAX (Accept: application/json).
    Contient : barre de répartition, pastilles filtres actifs, grille produits, pagination.
    Variables attendues : $produits (LengthAwarePaginator), $filtres, $repartition, $filtresActifs.
--}}

@php
    $total      = $produits->total();
    $aFiltres   = count($filtresActifs) > 0;
    $totalScore = array_sum(array_filter($repartition, fn($k) => $k !== 'sans_score', ARRAY_FILTER_USE_KEY));
@endphp

{{-- Barre de répartition des scores (cliquable) --}}
@if ($totalScore > 0)
    <div class="m1-score-bar" role="group" aria-label="Répartition des produits par score">
        @foreach (['A', 'B', 'C', 'D', 'E'] as $lettre)
            @php
                $nb   = $repartition[$lettre] ?? 0;
                $pct  = $totalScore > 0 ? round($nb / ($totalScore + ($repartition['sans_score'] ?? 0)) * 100, 1) : 0;
                $actif = ($filtres['score'] ?? '') === $lettre;
            @endphp
            @if ($nb > 0)
                <a href="{{ route('front.produits.index', array_merge(request()->except(['score', 'page']), $actif ? [] : ['score' => $lettre])) }}"
                   class="m1-score-bar__segment m1-score-bar__segment--{{ strtolower($lettre) }} {{ $actif ? 'm1-score-bar__segment--actif' : '' }}"
                   style="flex: {{ $pct }} 0 0%"
                   title="Score {{ $lettre }} : {{ $nb }} produit{{ $nb > 1 ? 's' : '' }} ({{ $pct }}&nbsp;%)"
                   data-score="{{ $lettre }}"
                   aria-pressed="{{ $actif ? 'true' : 'false' }}">
                    <span class="m1-score-bar__lettre">{{ $lettre }}</span>
                    <span class="m1-score-bar__nb">{{ $nb }}</span>
                </a>
            @endif
        @endforeach
        @if (($repartition['sans_score'] ?? 0) > 0)
            @php $nbSans = $repartition['sans_score'] @endphp
            <span class="m1-score-bar__segment m1-score-bar__segment--sans"
                  style="flex: {{ round($nbSans / ($totalScore + $nbSans) * 100, 1) }} 0 0%"
                  title="Sans score : {{ $nbSans }} produit{{ $nbSans > 1 ? 's' : '' }}">
                <span class="m1-score-bar__lettre">—</span>
                <span class="m1-score-bar__nb">{{ $nbSans }}</span>
            </span>
        @endif
    </div>
@endif

{{-- Barre résultats + pastilles filtres actifs --}}
<div class="m1-results-bar">
    <span class="m1-results-bar__count">
        <strong>{{ number_format($total, 0, ',', ' ') }}</strong>
        produit{{ $total > 1 ? 's' : '' }}
    </span>

    @if ($aFiltres)
        <div class="m1-chips" role="list" aria-label="Filtres actifs">
            @foreach ($filtresActifs as $chip)
                <a href="{{ $chip['url'] }}"
                   class="m1-chip"
                   role="listitem"
                   aria-label="Retirer le filtre {{ $chip['libelle'] }}">
                    {{ $chip['libelle'] }}
                    <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                </a>
            @endforeach
            <a href="{{ route('front.produits.index') }}" class="m1-chip m1-chip--reset">
                Tout effacer
            </a>
        </div>
    @endif
</div>

{{-- Grille produits --}}
@if ($total === 0)
    @if ($aFiltres)
        <x-front.empty-state icon="bi-search" title="Aucun produit ne correspond à votre recherche"
                             :reset="route('front.produits.index')">
            Essayez un autre mot-clé, une autre catégorie ou un autre filtre.
        </x-front.empty-state>
    @else
        <x-front.empty-state icon="bi-basket2" title="Le catalogue est encore vide">
            Les produits apparaîtront ici dès leur enregistrement par les producteurs et transformateurs.
        </x-front.empty-state>
    @endif
@else
    <div class="row g-4" id="m1-grille-produits">
        @foreach ($produits as $produit)
            <div class="col-sm-6 col-lg-4 col-xl-3">
                @include('pages.front.produits._carte', ['produit' => $produit])
            </div>
        @endforeach
    </div>

    <x-front.pagination :items="$produits" />
@endif
