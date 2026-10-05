@extends('layouts.front')

@section('title', 'Comparer des produits')

@section('content')
    @php($couleurs = \App\Http\Controllers\Front\ComparaisonController::couleurs())

    <x-front.page-header eyebrow="Comparaison" title="Comparer l'impact des produits" image="legumes-planche.webp"
                         subtitle="Empreinte carbone, eau, énergie, transport et emballage, côte à côte." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-4">
                    <form method="GET" action="{{ route('front.comparaison') }}" class="panel" data-max-selection="{{ $max }}">
                        <h2 class="panel-title"><i class="bi bi-check2-square" aria-hidden="true"></i>Choisir les produits</h2>
                        <p class="small text-muted">
                            Jusqu'à <strong>{{ $max }} produits</strong>.
                            @guest
                                <a href="{{ route('login') }}">Connectez-vous</a> pour en comparer jusqu'à {{ config('nutritrace.comparaison.connecte') }}.
                            @endguest
                        </p>

                        @if ($catalogue->isEmpty())
                            <p class="text-muted mb-0">Aucun produit disponible.</p>
                        @else
                            <div class="compare-picker mb-3">
                                @foreach ($catalogue as $item)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="produits[]" value="{{ $item->id }}" id="cmp-{{ $item->id }}"
                                               @checked(in_array($item->id, $selection, true))>
                                        <label class="form-check-label" for="cmp-{{ $item->id }}">
                                            {{ $item->nom }} <span class="small text-muted">· {{ $item->categorie?->nom }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-bar-chart me-1" aria-hidden="true"></i>Comparer</button>
                        @endif
                    </form>
                </div>

                <div class="col-lg-8">
                    @if ($limiteDepassee)
                        <div class="notice-panel mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <div>Vous pouvez comparer au maximum {{ $max }} produits : seuls les {{ $max }} premiers ont été retenus.</div>
                        </div>
                    @endif

                    @if ($produits->count() < 2)
                        <x-front.empty-state icon="bi-bar-chart" title="Sélectionnez au moins deux produits">
                            Cochez les produits à comparer dans la liste, puis lancez la comparaison.
                        </x-front.empty-state>
                    @else
                        <div class="data-card mb-4">
                            <div class="table-responsive">
                                <table class="table data-table align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col">Critère</th>
                                            @foreach ($produits as $produit)
                                                <th scope="col"><span class="d-inline-block rounded-circle me-1" style="width:.6rem;height:.6rem;background:{{ $couleurs[$loop->index] }}"></span>{{ $produit->nom }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">Score</th>
                                            @foreach ($produits as $produit)
                                                <td><x-front.score-badge :score="$produit->scoreGlobal()" taille="sm" /></td>
                                            @endforeach
                                        </tr>
                                        @foreach ($criteres as $cle => $critere)
                                            <tr>
                                                <th scope="row"><i class="bi {{ $critere['icone'] }} me-1 text-success" aria-hidden="true"></i>{{ $critere['libelle'] }} <span class="cell-sub d-block">{{ $critere['unite'] }}</span></th>
                                                @foreach ($produits as $produit)
                                                    @php($valeur = $valeurs[$produit->id][$cle])
                                                    <td>{{ $valeur === null ? '—' : number_format($valeur, $cle === 'transport' ? 0 : 2, ',', ' ') }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="panel">
                            <h2 class="panel-title"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>Graphique comparatif</h2>
                            <div class="compare-chart">
                                @foreach ($criteres as $cle => $critere)
                                    <div class="compare-metric">
                                        <h3><i class="bi {{ $critere['icone'] }} me-1 text-success" aria-hidden="true"></i>{{ $critere['libelle'] }} <span class="small text-muted fw-normal">({{ $critere['unite'] }})</span></h3>
                                        @foreach ($produits as $produit)
                                            @php($valeur = $valeurs[$produit->id][$cle])
                                            @php($pourcentage = $valeur && $maxima[$cle] > 0 ? round($valeur / $maxima[$cle] * 100) : 0)
                                            <div class="compare-row">
                                                <span class="text-truncate">{{ $produit->nom }}</span>
                                                <div class="compare-track" role="img" aria-label="{{ $produit->nom }} : {{ $valeur ?? 'non mesuré' }}">
                                                    <div class="compare-fill" style="--value: {{ $pourcentage }}%; --couleur: {{ $couleurs[$loop->index] }}"></div>
                                                </div>
                                                <strong>{{ $valeur === null ? '—' : number_format($valeur, $cle === 'transport' ? 0 : 2, ',', ' ') }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <p class="small text-muted mt-3 mb-0">Moyennes calculées sur les lots dont l'empreinte a été mesurée. Plus la barre est courte, plus l'impact est faible.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
