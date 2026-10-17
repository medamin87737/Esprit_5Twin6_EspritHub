{{--
    _lot_detail.blade.php — Module 1 (Ghada)
    Fragment HTML retourné en JSON par ProduitController::lotJson().
    Variables : $lot (Lot chargé), $produit, $empreinte, $parts, $facteurs.
--}}
<div class="m1-lot-detail">

    {{-- ── Informations générales ────────────────────────────────────────── --}}
    <dl class="produit-facts mb-3">
        <div>
            <dt>Numéro de lot</dt>
            <dd>{{ $lot->numero_lot }}</dd>
        </div>
        <div>
            <dt>Quantité</dt>
            <dd>{{ number_format($lot->quantite, 0, ',', ' ') }} unités</dd>
        </div>
        <div>
            <dt>Production</dt>
            <dd>{{ $lot->date_production?->format('d/m/Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Péremption</dt>
            <dd class="{{ $lot->estPerime() ? 'text-danger fw-bold' : '' }}">
                {{ $lot->date_peremption?->format('d/m/Y') ?? '—' }}
                @if ($lot->estPerime())
                    <span class="status-pill status-expired ms-1">Périmé</span>
                @endif
            </dd>
        </div>
        @if ($lot->acteurCourant)
            <div>
                <dt>Détenteur actuel</dt>
                <dd>{{ $lot->acteurCourant->nom }}</dd>
            </div>
        @endif
    </dl>

    {{-- ── Score du lot ──────────────────────────────────────────────────── --}}
    @if ($empreinte)
        <div class="d-flex align-items-center gap-3 mb-3">
            <x-front.score-badge :score="$empreinte->score" :co2="$empreinte->co2_total" taille="md" />
            @if (! empty($parts))
                <span class="small text-muted">
                    CO₂ total : {{ number_format($empreinte->co2_total, 2, ',', ' ') }} kg CO₂e/unité
                </span>
            @endif
        </div>

        {{-- Part de chaque indicateur dans le CO₂ --}}
        @if (! empty($parts))
            @can('voir-indicateurs')
                <div class="mb-3">
                    <p class="small fw-600 mb-1">Contribution au CO₂ par indicateur :</p>
                    <div class="parcours-indicateurs">
                        @foreach ($parts as $type => $part)
                            @if ($part['valeur'] > 0)
                                <span class="indicateur-chip">
                                    <i class="bi {{ \App\Models\Indicateur::ICONES[$type] ?? 'bi-speedometer2' }}"
                                       aria-hidden="true"></i>
                                    {{ config('nutritrace.options.indicateur_types.' . $type, $type) }}
                                    <strong>{{ $part['pct'] }} %</strong>
                                    <span class="text-muted">({{ number_format($part['valeur'], 3, ',', ' ') }} kg CO₂e/u)</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endcan
        @endif
    @else
        <p class="text-muted small mb-3">Aucune empreinte carbone calculée pour ce lot.</p>
    @endif

    {{-- ── Étapes du parcours ────────────────────────────────────────────── --}}
    @if ($lot->etapes->isNotEmpty())
        <h3 class="h6 mb-2"><i class="bi bi-geo-alt me-1 text-success" aria-hidden="true"></i>Parcours ({{ $lot->etapes->count() }} étape{{ $lot->etapes->count() > 1 ? 's' : '' }})</h3>
        <ul class="parcours-infos mb-3">
            @foreach ($lot->etapes as $etape)
                <li>
                    <i class="bi {{ $etape->icone() }}" aria-hidden="true"></i>
                    <strong>{{ $etape->typeLabel() }}</strong>
                    @if ($etape->lieu)
                        — {{ $etape->lieu }}
                    @endif
                    @if ($etape->acteur)
                        <span class="text-muted">({{ $etape->acteur->nom }})</span>
                    @endif
                    <span class="text-muted">· {{ $etape->date_heure?->format('d/m/Y') }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- ── Analyses ──────────────────────────────────────────────────────── --}}
    @if ($lot->analyses->isNotEmpty())
        <h3 class="h6 mb-2"><i class="bi bi-clipboard2-pulse me-1 text-success" aria-hidden="true"></i>Analyses ({{ $lot->analyses->count() }})</h3>
        <ul class="parcours-infos mb-3">
            @foreach ($lot->analyses as $analyse)
                <li>
                    <i class="bi {{ $analyse->icone() }}" aria-hidden="true"></i>
                    {{ $analyse->typeLabel() }}
                    <span class="status-pill {{ $analyse->resultatClasse() }} ms-1">
                        {{ $analyse->resultatLabel() }}
                    </span>
                    @if ($analyse->laboratoire)
                        <span class="text-muted">· {{ $analyse->laboratoire->nom }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    {{-- ── Lien parcours complet ─────────────────────────────────────────── --}}
    <div class="parcours-actions">
        <a href="{{ route('front.lots.show', $lot) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-map me-1" aria-hidden="true"></i>Voir le parcours complet
        </a>
    </div>

</div>
