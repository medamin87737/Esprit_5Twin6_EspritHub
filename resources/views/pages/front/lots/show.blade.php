@extends('layouts.front')

@section('title', 'Lot ' . $lot->numero_lot)

@section('content')
    <x-front.page-header eyebrow="Parcours du lot" :title="$lot->produit?->nom" image="distributeur-camion.webp"
                         :subtitle="'Lot ' . $lot->numero_lot" />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <nav aria-label="Fil d'Ariane" class="d-flex flex-wrap justify-content-between gap-2 mb-4">
                <a href="{{ route('front.lots.search') }}" class="small"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Tracer un autre lot</a>
                @if ($lot->produit)
                    <a href="{{ route('front.produits.show', $lot->produit) }}" class="small">Fiche du produit <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                @endif
            </nav>

            <div class="lot-summary">
                <div>
                    <span class="eyebrow mb-1">{{ $lot->produit?->categorie?->nom }}</span>
                    <h2 class="h3 mb-1">{{ $lot->produit?->nom }}</h2>
                    <div class="text-muted mb-2"><i class="bi bi-upc me-1" aria-hidden="true"></i>{{ $lot->numero_lot }}</div>
                    @if ($lot->produit?->certifications->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($lot->produit->certifications as $certification)
                                <x-front.certification-badge :certification="$certification" />
                            @endforeach
                        </div>
                    @endif
                </div>
                <dl class="lot-facts">
                    <div><dt>Quantité</dt><dd>{{ number_format($lot->quantite, 0, ',', ' ') }} unités</dd></div>
                    <div><dt>Production</dt><dd>{{ $lot->date_production?->format('d/m/Y') }}</dd></div>
                    <div><dt>Péremption</dt><dd>{{ $lot->date_peremption?->format('d/m/Y') }}@if ($lot->estPerime()) <span class="status-pill status-expired ms-1">Périmé</span>@endif</dd></div>
                    <div><dt>Détenu par</dt><dd>{{ $lot->acteurCourant?->nom ?? '—' }}</dd></div>
                    <div><dt>Analyses qualité</dt><dd><x-front.analyses-badge :synthese="$lot->syntheseAnalyses()" /></dd></div>
                    <div>
                        <dt>Score et CO₂ total</dt>
                        <dd><x-front.score-badge :score="$lot->empreinteCarbone?->score" :co2="$lot->empreinteCarbone?->co2_total" /></dd>
                    </div>
                </dl>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-lg-7">
                    <h2 class="h4 mb-3">Les étapes du parcours</h2>
                    @if ($lot->etapes->isEmpty())
                        <x-front.empty-state icon="bi-hourglass-split" title="Parcours en cours de saisie">
                            Aucune étape n'a encore été enregistrée pour ce lot.
                        </x-front.empty-state>
                    @else
                        <x-front.parcours :etapes="$lot->etapes" :indicateurs="auth()->user()?->can('voir-indicateurs') ?? false" />
                    @endif
                </div>

                <div class="col-lg-5">
                    <h2 class="h4 mb-3">Sur la carte</h2>
                    <x-front.carte :points="$points" trace titre="Carte du parcours du lot" class="mb-4" />

                    <div class="panel">
                        <h2 class="panel-title"><i class="bi bi-speedometer2" aria-hidden="true"></i>Indicateurs environnementaux</h2>
                        @can('voir-indicateurs')
                            @if ($totaux->isEmpty())
                                <p class="text-muted mb-0">Aucun indicateur saisi pour ce lot.</p>
                            @else
                                <ul class="alert-list">
                                    @foreach ($totaux as $total)
                                        <li>
                                            <span><i class="bi {{ $total['indicateur']->icone() }} me-2 text-success" aria-hidden="true"></i>{{ $total['indicateur']->typeLabel() }}</span>
                                            <strong>{{ number_format($total['valeur'], 2, ',', ' ') }} {{ $total['unite'] }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($lot->empreinteCarbone)
                                    <p class="small text-muted mt-3 mb-0">Méthode : {{ $lot->empreinteCarbone->methode }} · calculé le {{ $lot->empreinteCarbone->date_calcul?->format('d/m/Y') }}</p>
                                @endif
                            @endif
                        @else
                            <div class="notice-panel">
                                <i class="bi bi-lock" aria-hidden="true"></i>
                                <div>
                                    Les indicateurs détaillés (eau, énergie, transport, emballage) sont réservés aux membres.
                                    <a href="{{ route('login') }}">Connectez-vous</a> ou <a href="{{ route('register') }}">créez un compte</a>.
                                </div>
                            </div>
                        @endcan
                    </div>

                    <div class="panel mt-4">
                        <h2 class="panel-title"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>Analyses qualité</h2>
                        @if ($lot->analyses->isEmpty())
                            <p class="text-muted mb-0">Aucune analyse de laboratoire n'a encore été déclarée pour ce lot.</p>
                        @else
                            <ul class="alert-list">
                                @foreach ($lot->analyses as $analyse)
                                    <li>
                                        <span>
                                            <i class="bi {{ $analyse->icone() }} me-2 text-success" aria-hidden="true"></i>{{ $analyse->typeLabel() }}
                                            <span class="d-block small text-muted">{{ $analyse->laboratoire?->nom }} · prélevé le {{ $analyse->date_prelevement?->format('d/m/Y') }}</span>
                                            @if ($analyse->resultat === 'non_conforme' && $analyse->commentaire)
                                                <span class="d-block small text-danger">{{ $analyse->commentaire }}</span>
                                            @endif
                                            @can('telechargerRapport', $analyse)
                                                <a href="{{ route('front.analyses.rapport', $analyse) }}" class="small"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Rapport PDF</a>
                                            @endcan
                                        </span>
                                        <span class="status-pill {{ $analyse->resultatClasse() }}">{{ $analyse->resultatLabel() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @guest
                                @if ($lot->analyses->whereNotNull('rapport')->isNotEmpty())
                                    <p class="small text-muted mt-3 mb-0"><i class="bi bi-lock me-1" aria-hidden="true"></i><a href="{{ route('login') }}">Connectez-vous</a> pour télécharger les rapports du laboratoire.</p>
                                @endif
                            @endguest
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
