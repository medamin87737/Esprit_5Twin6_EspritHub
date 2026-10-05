@extends('layouts.pro')

@section('title', 'Lot ' . $lot->numero_lot)

@section('pro_content')
    @php($chezMoi = $lot->estChez(auth()->user()->acteur))

    <div class="pro-toolbar">
        <a href="{{ route('pro.lots.index', $chezMoi ? [] : ['vue' => 'historique']) }}" class="small"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Mes lots</a>
        <a href="{{ route('front.lots.show', $lot) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1" aria-hidden="true"></i>Page publique du lot</a>
    </div>

    <div class="lot-summary">
        <div>
            <span class="eyebrow mb-1">{{ $lot->produit?->categorie?->nom }}</span>
            <h2 class="h3 mb-1">{{ $lot->produit?->nom }}</h2>
            <div class="text-muted"><i class="bi bi-upc me-1" aria-hidden="true"></i>{{ $lot->numero_lot }}</div>
            @if ($chezMoi)
                <span class="status-pill status-valid mt-2"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>En votre possession</span>
            @else
                <span class="status-pill status-pending mt-2"><i class="bi bi-lock me-1" aria-hidden="true"></i>Transféré à {{ $lot->acteurCourant?->nom ?? '—' }} · lecture seule</span>
            @endif
        </div>
        <dl class="lot-facts">
            <div><dt>Quantité</dt><dd>{{ number_format($lot->quantite, 0, ',', ' ') }} unités</dd></div>
            <div><dt>Production</dt><dd>{{ $lot->date_production?->format('d/m/Y') }}</dd></div>
            <div><dt>Péremption</dt><dd>{{ $lot->date_peremption?->format('d/m/Y') }}</dd></div>
            <div><dt>Score recalculé</dt><dd><x-front.score-badge :score="$lot->empreinteCarbone?->score" :co2="$lot->empreinteCarbone?->co2_total" /></dd></div>
        </dl>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-7">
            <div class="pro-toolbar">
                <h2 class="h4 mb-0">Parcours</h2>
                @can('ajouterEtape', $lot)
                    <a href="{{ route('pro.etapes.create', $lot) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Ajouter mon étape</a>
                @endcan
            </div>

            @if ($lot->etapes->isEmpty())
                <x-front.empty-state icon="bi-hourglass-split" title="Aucune étape enregistrée">
                    Ajoutez votre étape ({{ collect(auth()->user()->droitPro('etapes'))->map(fn ($t) => config('nutritrace.options.etape_types.' . $t))->join(', ', ' ou ') }}) pour démarrer le parcours.
                </x-front.empty-state>
            @else
                <x-front.parcours :etapes="$lot->etapes" indicateurs actions />
            @endif
        </div>

        <div class="col-lg-5">
            @can('transferer', $lot)
                <div class="panel mb-4">
                    <h2 class="panel-title"><i class="bi bi-send" aria-hidden="true"></i>Transférer au prochain acteur</h2>
                    @if ($destinataires->isEmpty())
                        <p class="text-muted mb-0">Aucun acteur de l'étape suivante ne dispose encore d'un compte professionnel.</p>
                    @else
                        <form method="POST" action="{{ route('pro.lots.transfert', $lot) }}"
                              onsubmit="return confirm('Transférer ce lot ? Vos étapes seront verrouillées et vous ne pourrez plus les modifier.');">
                            @csrf
                            <x-front.champ name="acteur_id" label="Destinataire" type="select" required placeholder="Choisir l'acteur suivant…"
                                           :options="$destinataires->mapWithKeys(fn ($a) => [$a->id => $a->nom . ' (' . $a->user->roleLabel() . ')'])" />
                            <p class="small text-muted">Après le transfert, le destinataire devient l'acteur courant et vos étapes sont verrouillées.</p>
                            <button type="submit" class="btn btn-gold w-100"><i class="bi bi-send me-1" aria-hidden="true"></i>Transférer le lot</button>
                        </form>
                    @endif
                </div>
            @elseif ($chezMoi)
                <div class="notice-panel mb-4">
                    <i class="bi bi-shop" aria-hidden="true"></i>
                    <div>Vous êtes le dernier maillon de la chaîne : enregistrez la distribution puis la vente de ce lot.</div>
                </div>
            @endcan

            <div class="panel mb-4">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="panel-title mb-0"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>Analyses qualité</h2>
                    @can('ajouterAnalyse', $lot)
                        <a href="{{ route('pro.analyses.create', $lot) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Ajouter</a>
                    @endcan
                </div>
                @if ($lot->analyses->isEmpty())
                    <p class="text-muted mb-0">Aucune analyse déclarée. {{ $chezMoi ? 'Ajoutez le résultat du laboratoire qui a contrôlé ce lot.' : '' }}</p>
                @else
                    <ul class="alert-list">
                        @foreach ($lot->analyses as $analyse)
                            <li>
                                <span>
                                    <i class="bi {{ $analyse->icone() }} me-2 text-success" aria-hidden="true"></i>{{ $analyse->typeLabel() }}
                                    <span class="d-block small text-muted">{{ $analyse->numero }} · {{ $analyse->laboratoire?->nom }} · {{ $analyse->date_prelevement?->format('d/m/Y') }}</span>
                                    @if ($analyse->rapport)
                                        <a href="{{ route('front.analyses.rapport', $analyse) }}" class="small"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Rapport</a>
                                    @endif
                                    @can('update', $analyse)
                                        <span class="d-flex gap-2 mt-1">
                                            <a href="{{ route('pro.analyses.edit', $analyse) }}" class="btn btn-sm btn-outline-primary py-0">Modifier</a>
                                            <form method="POST" action="{{ route('pro.analyses.destroy', $analyse) }}" onsubmit="return confirm('Supprimer l\'analyse {{ $analyse->numero }} ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0">Supprimer</button>
                                            </form>
                                        </span>
                                    @endcan
                                </span>
                                <span class="status-pill {{ $analyse->resultatClasse() }}">{{ $analyse->resultatLabel() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <h2 class="h5 mb-3">Carte du parcours</h2>
            <x-front.carte :points="$points" trace titre="Carte du parcours du lot" />
        </div>
    </div>
@endsection
