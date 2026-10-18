@extends('layouts.front')

@section('title', $produit->nom)

@push('scripts')
    @vite('resources/assets/front/js/module1.js')
@endpush

@section('content')
    <x-front.page-header :eyebrow="$produit->categorie?->nom ?? 'Produit'" :title="$produit->nom" image="marche-fruits-legumes.webp"
                         :subtitle="'Origine : ' . $produit->origine" />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">

            {{-- Fil d'Ariane + bouton Comparer --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <nav aria-label="Fil d'Ariane">
                    <a href="{{ route('front.produits.index') }}" class="small">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Retour au catalogue
                    </a>
                </nav>

                @php
                    $selectionComparer = session('m1_comparaison', []);
                    $dejaSelectionne   = in_array($produit->id, $selectionComparer, true);
                    $maxComp = auth()->check() && auth()->user()->can('comparer-sans-limite')
                        ? (int) config('nutritrace.comparaison.connecte')
                        : (int) config('nutritrace.comparaison.visiteur');
                    $limitePleine = count($selectionComparer) >= $maxComp && ! $dejaSelectionne;
                @endphp

                @if ($dejaSelectionne)
                    <form method="POST"
                          action="{{ route('front.produits.comparaison.retirer', $produit->id) }}"
                          class="m1-form-comparer" data-ajax="true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Dans la comparaison
                        </button>
                    </form>
                @elseif (! $limitePleine)
                    <form method="POST"
                          action="{{ route('front.produits.comparaison.ajouter') }}"
                          class="m1-form-comparer m1-carte-produit" data-produit-id="{{ $produit->id }}" data-ajax="true">
                        @csrf
                        <input type="hidden" name="produit_id" value="{{ $produit->id }}">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-bar-chart-steps me-1" aria-hidden="true"></i>Comparer
                        </button>
                    </form>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                            title="Limite de {{ $maxComp }} produits atteinte">
                        <i class="bi bi-lock me-1" aria-hidden="true"></i>Limite atteinte
                    </button>
                @endif
            </div>

            {{-- ── KPIs ──────────────────────────────────────────────────── --}}
            <div class="m1-kpis mb-4">
                <div class="eco-kpi">
                    <strong>{{ $produit->lots->count() }}</strong>
                    <span>Lots tracés</span>
                </div>
                <div class="eco-kpi">
                    <strong>{{ $nbEtapes }}</strong>
                    <span>Étapes</span>
                </div>
                <div class="eco-kpi">
                    @php $co2 = $produit->co2Moyen() @endphp
                    <strong>{{ $co2 !== null ? number_format($co2, 2, ',', ' ') : '—' }}</strong>
                    <span>kg CO₂e/u</span>
                </div>
                <div class="eco-kpi">
                    <strong>{{ $produit->certifications->count() }}</strong>
                    <span>Labels valides</span>
                </div>
                @if ($pctConformes !== null)
                    <div class="eco-kpi">
                        <strong>{{ $pctConformes }} %</strong>
                        <span>Lots conformes</span>
                    </div>
                @endif
            </div>

            <div class="row g-4">
                {{-- ── Colonne gauche ──────────────────────────────────── --}}
                <div class="col-lg-5">
                    <div class="produit-visuel mb-4 position-relative">
                        @if ($produit->image)
                            <img src="{{ asset('storage/' . $produit->image) }}" alt="{{ $produit->nom }}">
                        @else
                            <span class="product-card-placeholder"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                        @endif
                        <button
                            type="button"
                            class="nt-qualite-avatar nt-qualite-avatar--lg"
                            data-chat-produit
                            data-produit-id="{{ $produit->id }}"
                            data-produit-nom="{{ $produit->nom }}"
                            data-login-url="{{ route('login') }}"
                            title="Assistant qualité — {{ $produit->nom }}"
                            aria-label="Poser une question qualité sur {{ $produit->nom }}"
                        >
                            <span class="nt-qualite-avatar__pulse" aria-hidden="true"></span>
                            <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                            <span class="nt-qualite-avatar__badge">Qualité</span>
                        </button>
                    </div>

                    {{-- Score global --}}
                    <div class="panel mb-4">
                        <h2 class="panel-title"><i class="bi bi-globe-europe-africa" aria-hidden="true"></i>Score environnemental</h2>
                        <x-front.score-badge :score="$produit->scoreGlobal()" :co2="$produit->co2Moyen()" taille="lg" />
                        <p class="small text-muted mt-3 mb-0">
                            Moyenne des empreintes carbone des lots de ce produit, en kg de CO₂e par unité.
                        </p>
                    </div>

                    {{-- Courbe SVG CO₂ --}}
                    @include('pages.front.produits._courbe_co2', ['lotsAvecScore' => $lotsAvecScore])

                    {{-- Recommandations IA (chargement différé) --}}
                    <div id="m1-zone-recommandations"
                         data-url="{{ route('front.produits.recommandations', $produit) }}"
                         class="mb-4">
                        <p class="small text-muted">
                            <span class="m1-spinner" aria-hidden="true"></span>
                            Chargement des alternatives plus écologiques…
                        </p>
                    </div>
                </div>

                {{-- ── Colonne droite ──────────────────────────────────── --}}
                <div class="col-lg-7">

                    {{-- Informations + composition en pastilles --}}
                    <div class="panel mb-4">
                        <h2 class="panel-title"><i class="bi bi-info-circle" aria-hidden="true"></i>Informations</h2>
                        <dl class="produit-facts mb-3">
                            <div><dt>Catégorie</dt><dd>{{ $produit->categorie?->nom ?? '—' }}</dd></div>
                            <div><dt>Code-barres</dt><dd>{{ $produit->code_barres }}</dd></div>
                            <div><dt>Origine</dt><dd>{{ $produit->origine }}</dd></div>
                            <div><dt>Référencé le</dt><dd>{{ $produit->created_at?->format('d/m/Y') }}</dd></div>
                        </dl>
                        @if ($produit->description)
                            <p>{{ $produit->description }}</p>
                        @endif
                        <h3 class="h6 mt-3">Composition</h3>
                        @if ($produit->composition)
                            <div class="m1-ingredients">
                                @foreach (array_filter(array_map('trim', explode(',', $produit->composition))) as $ingredient)
                                    <span class="m1-ingredient-chip">{{ $ingredient }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">Non renseignée.</p>
                        @endif
                    </div>

                    {{-- Certifications avec barre de validité --}}
                    <div class="panel mb-4">
                        <h2 class="panel-title"><i class="bi bi-award" aria-hidden="true"></i>Certifications valides</h2>
                        @if ($produit->certifications->isEmpty())
                            <p class="text-muted mb-0">Aucune certification en cours de validité pour ce produit.</p>
                        @else
                            <ul class="alert-list">
                                @foreach ($produit->certifications as $certification)
                                    @php
                                        $jours          = $certification->joursRestants();
                                        $expireBientot  = $certification->expireBientot();
                                        $dateExp        = $certification->date_expiration;
                                        $dateObt        = $certification->date_obtention;
                                        $dureeTotal     = $dateObt && $dateExp
                                            ? max($dateObt->diffInDays($dateExp), 1)
                                            : null;
                                        $pctRestant     = ($dureeTotal && $jours !== null)
                                            ? min(100, round($jours / $dureeTotal * 100))
                                            : null;
                                    @endphp
                                    <li class="flex-column align-items-start gap-1">
                                        <div class="d-flex align-items-center justify-content-between w-100 gap-2">
                                            <span>
                                                <x-front.certification-badge :certification="$certification" />
                                                <span class="small text-muted ms-1">n° {{ $certification->numero }}
                                                    @if ($certification->organisme)
                                                        · {{ $certification->organisme->nom }}
                                                    @endif
                                                </span>
                                            </span>
                                            <span class="small text-muted text-nowrap">
                                                @if ($jours !== null)
                                                    @if ($expireBientot)
                                                        <span class="status-pill status-pending">
                                                            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                                            {{ $jours }} j restants
                                                        </span>
                                                    @else
                                                        jusqu'au {{ $dateExp?->format('d/m/Y') }}
                                                    @endif
                                                @endif
                                            </span>
                                        </div>
                                        @if ($pctRestant !== null)
                                            <div class="m1-validite-bar w-100">
                                                <div class="m1-validite-bar__fill {{ $expireBientot ? 'm1-validite-bar__fill--alerte' : '' }}"
                                                     style="width: {{ $pctRestant }}%"
                                                     role="progressbar"
                                                     aria-valuenow="{{ $pctRestant }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100"
                                                     aria-label="Validité restante : {{ $pctRestant }} %"></div>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Lots avec onglets dynamiques --}}
                    <div class="panel mb-4">
                        <h2 class="panel-title"><i class="bi bi-upc-scan" aria-hidden="true"></i>Lots tracés ({{ $produit->lots->count() }})</h2>
                        @if ($produit->lots->isEmpty())
                            <p class="text-muted mb-0">Aucun lot enregistré pour ce produit.</p>
                        @else
                            {{-- Onglets --}}
                            <div class="m1-onglets-lots" id="m1-onglets-lots" role="tablist">
                                @foreach ($produit->lots as $i => $lot)
                                    <button type="button"
                                            class="m1-onglet-lot {{ $i === 0 ? 'm1-onglet-lot--actif' : '' }}"
                                            data-url="{{ route('front.produits.lot_json', [$produit, $lot]) }}"
                                            role="tab"
                                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                                            aria-label="Lot {{ $lot->numero_lot }}">
                                        <x-front.score-badge :score="$lot->empreinteCarbone?->score" taille="sm" />
                                        {{ $lot->numero_lot }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Zone de détail : pré-chargée avec le premier lot --}}
                            @php $premierLot = $produit->lots->first() @endphp
                            <div id="m1-lot-detail" role="tabpanel">
                                @if ($premierLot)
                                    @php
                                        $premierLot->load([
                                            'acteurCourant',
                                            'empreinteCarbone.indicateurs',
                                            'etapes' => fn ($q) => $q->with('acteur:id,nom')->orderBy('date_heure'),
                                            'analyses' => fn ($q) => $q->with('laboratoire:id,nom')->orderBy('date_resultat'),
                                        ]);
                                        $facteurs  = config('nutritrace.facteurs_co2', []);
                                        $empreinte = $premierLot->empreinteCarbone;
                                        $co2Total  = $empreinte?->co2_total ?? 0;
                                        $parts     = [];
                                        if ($empreinte && $co2Total > 0 && $premierLot->quantite > 0) {
                                            foreach (['eau', 'energie', 'transport', 'emballage'] as $type) {
                                                $somme   = $empreinte->indicateurs->where('type', $type)->sum('valeur');
                                                $contrib = $somme * ($facteurs[$type] ?? 0) / $premierLot->quantite;
                                                $parts[$type] = ['valeur' => round($contrib, 3), 'pct' => 0];
                                            }
                                            $sommeParts = array_sum(array_column($parts, 'valeur'));
                                            if ($sommeParts > 0) {
                                                foreach ($parts as $type => &$part) {
                                                    $part['pct'] = round($part['valeur'] / $sommeParts * 100);
                                                }
                                                unset($part);
                                            }
                                        }
                                    @endphp
                                    @include('pages.front.produits._lot_detail', [
                                        'lot'      => $premierLot,
                                        'produit'  => $produit,
                                        'empreinte' => $empreinte,
                                        'parts'    => $parts,
                                        'facteurs' => $facteurs,
                                    ])
                                @endif
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </section>

    @include('pages.front.produits._barre_comparaison')
@endsection
