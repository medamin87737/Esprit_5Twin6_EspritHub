@extends('layouts.front')

@section('title', $produit->nom)

@section('content')
    <x-front.page-header :eyebrow="$produit->categorie?->nom ?? 'Produit'" :title="$produit->nom" image="marche-fruits-legumes.webp"
                         :subtitle="'Origine : ' . $produit->origine" />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="{{ route('front.produits.index') }}" class="small"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Retour au catalogue</a>
            </nav>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="produit-visuel mb-4">
                        @if ($produit->image)
                            <img src="{{ asset('storage/' . $produit->image) }}" alt="{{ $produit->nom }}">
                        @else
                            <span class="product-card-placeholder"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                        @endif
                    </div>

                    <div class="panel">
                        <h2 class="panel-title"><i class="bi bi-globe-europe-africa" aria-hidden="true"></i>Score environnemental</h2>
                        <x-front.score-badge :score="$produit->scoreGlobal()" :co2="$produit->co2Moyen()" taille="lg" />
                        <p class="small text-muted mt-3 mb-0">
                            Moyenne des empreintes carbone des lots de ce produit, en kg de CO₂e par unité.
                        </p>
                    </div>
                </div>

                <div class="col-lg-7">
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
                        <p class="mb-0 {{ $produit->composition ? '' : 'text-muted' }}">{{ $produit->composition ?: 'Non renseignée.' }}</p>
                    </div>

                    <div class="panel mb-4">
                        <h2 class="panel-title"><i class="bi bi-award" aria-hidden="true"></i>Certifications valides</h2>
                        @if ($produit->certifications->isEmpty())
                            <p class="text-muted mb-0">Aucune certification en cours de validité pour ce produit.</p>
                        @else
                            <ul class="alert-list">
                                @foreach ($produit->certifications as $certification)
                                    <li>
                                        <span><x-front.certification-badge :certification="$certification" /> <span class="small text-muted ms-1">n° {{ $certification->numero }} · {{ $certification->organisme?->nom }}</span></span>
                                        <span class="small text-muted text-nowrap">jusqu'au {{ $certification->date_expiration?->format('d/m/Y') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="panel mb-4">
                        <h2 class="panel-title"><i class="bi bi-upc-scan" aria-hidden="true"></i>Lots tracés ({{ $produit->lots->count() }})</h2>
                        @if ($produit->lots->isEmpty())
                            <p class="text-muted mb-0">Aucun lot enregistré pour ce produit.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table data-table align-middle mb-0">
                                    <thead>
                                        <tr><th scope="col">Lot</th><th scope="col">Production</th><th scope="col">Étapes</th><th scope="col">Score</th><th scope="col">Analyses</th><th scope="col"><span class="visually-hidden">Parcours</span></th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($produit->lots as $lot)
                                            <tr>
                                                <td class="cell-title">{{ $lot->numero_lot }}</td>
                                                <td>{{ $lot->date_production?->format('d/m/Y') }}</td>
                                                <td>{{ $lot->etapes_count }}</td>
                                                <td><x-front.score-badge :score="$lot->empreinteCarbone?->score" taille="sm" /></td>
                                                <td><x-front.analyses-badge :synthese="$lot->syntheseAnalyses()" /></td>
                                                <td class="cell-actions"><a href="{{ route('front.lots.show', $lot) }}" class="btn btn-sm btn-outline-primary">Parcours</a></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="panel">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h2 class="panel-title mb-0"><i class="bi bi-chat-square-text" aria-hidden="true"></i>Avis vérifiés des consommateurs</h2>
                            @can('signaler')
                                <a href="{{ route('consommateur.signalements.create', $produit) }}" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-flag me-1" aria-hidden="true"></i>Signaler
                                </a>
                            @endcan
                            @guest
                                <a href="{{ route('login') }}" class="small">Connectez-vous pour signaler un problème</a>
                            @endguest
                        </div>
                        @forelse ($produit->signalements as $signalement)
                            <div class="avis-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong>{{ $signalement->motifLabel() }}</strong>
                                    <span class="small text-muted">{{ $signalement->created_at?->format('d/m/Y') }}</span>
                                </div>
                                <p class="mb-1">{{ $signalement->description }}</p>
                                <span class="small text-muted">{{ \Illuminate\Support\Str::of($signalement->user?->name)->explode(' ')->first() }} · avis validé par NutriTrace</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Aucun avis validé pour ce produit.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
