@php
    $risque = session('risque_ia');
    $produit = $lot->produit;
    $nomProduit = $produit?->nom ?? 'ce produit';
    $synthese = $synthese ?? null;
@endphp

{{-- Grande carte produit + bouton chat IA --}}
<article class="nt-q-hero" aria-labelledby="nt-q-lot-name">
    <div class="nt-q-hero__photo">
        @if ($produit?->image)
            <img src="{{ asset('storage/'.$produit->image) }}" alt="{{ $nomProduit }}">
        @else
            <span class="nt-q-hero__placeholder" aria-hidden="true"><i class="bi bi-basket"></i></span>
        @endif
        @if ($synthese)
            <span class="nt-q-hero__verdict nt-q-hero__verdict--{{ $synthese['classe'] }}">
                @if ($synthese['statut'] === 'conforme') ✓ @elseif ($synthese['statut'] === 'non_conforme') ✕ @else … @endif
                {{ $synthese['libelle'] }}
            </span>
        @endif
    </div>

    <div class="nt-q-hero__content">
        <p class="nt-q-hero__cat">{{ $produit?->categorie?->nom ?? 'Produit' }} · {{ $produit?->origine }}</p>
        <h2 id="nt-q-lot-name" class="nt-q-hero__name">{{ $nomProduit }}</h2>
        <p class="nt-q-hero__ref">
            <i class="bi bi-upc" aria-hidden="true"></i>
            {{ $lot->numero_lot }}
            @if ($produit?->code_barres)
                <span class="nt-q-hero__ean">EAN {{ $produit->code_barres }}</span>
            @endif
        </p>

        <dl class="nt-q-hero__meta">
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
                <dd class="nt-q-hero__peremption nt-q-hero__peremption--{{ $lot->alertePeremption() ?? 'valide' }}">
                    {{ $lot->date_peremption?->format('d/m/Y') ?? '—' }}
                    @if ($lot->joursAvantPeremption() !== null && $lot->joursAvantPeremption() >= 0)
                        <small>({{ $lot->joursAvantPeremption() }} j)</small>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Composition</dt>
                <dd class="nt-q-hero__compo">{{ \Illuminate\Support\Str::limit($produit?->composition, 90) }}</dd>
            </div>
        </dl>

        @if ($synthese)
            <p class="nt-q-hero__stats">
                <span class="nt-q-stat nt-q-stat--total">{{ $synthese['total'] }} analyse(s)</span>
                <span class="nt-q-stat nt-q-stat--ok">✓ {{ $synthese['conformes'] }} conforme(s)</span>
                @if ($synthese['non_conformes'] > 0)
                    <span class="nt-q-stat nt-q-stat--ko">✕ {{ $synthese['non_conformes'] }} non conforme(s)</span>
                @endif
                @if ($synthese['en_attente'] > 0)
                    <span class="nt-q-stat nt-q-stat--wait">… {{ $synthese['en_attente'] }} en attente</span>
                @endif
            </p>
        @endif

        @if ($produit?->certifications?->isNotEmpty())
            <p class="nt-q-hero__certifs">
                @foreach ($produit->certifications as $certification)
                    <span class="nt-q-certif">
                        <i class="bi {{ $certification->icone() }}" aria-hidden="true"></i>
                        {{ $certification->typeLabel() }} · {{ $certification->organisme?->nom }}
                    </span>
                @endforeach
            </p>
        @endif

        @if (! empty($synthese['alertes']) && $synthese['statut'] !== 'conforme')
            <div class="nt-q-vigilance mt-3">
                <p class="nt-q-vigilance__head"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> <strong>Points de vigilance</strong></p>
                <ul>
                    @foreach ($synthese['alertes'] as $alerte)
                        <li>{{ $alerte }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @can('espace-consommateur')
        <button
            type="button"
            class="nt-chat__fab nt-chat__fab--batterie nt-q-hero__chat"
            data-nt-chat-open
            aria-expanded="false"
            aria-controls="nt-chat-panel"
            title="Assistant qualité IA"
        >
            <span class="nt-qualite-avatar__pulse" aria-hidden="true"></span>
            <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
            <span class="nt-qualite-avatar__badge">Qualité</span>
            <span class="visually-hidden">Ouvrir l'assistant qualité IA</span>
        </button>
    @endcan

    @if (is_array($risque))
        @php
            $risquePayload = [
                'score' => $risque['score'] ?? null,
                'niveau' => $risque['niveau'] ?? null,
                'libelle' => $risque['libelle'] ?? null,
                'aptitude' => $risque['aptitude'] ?? null,
                'aptitude_label' => $risque['aptitude_label'] ?? null,
                'conseil_sante' => $risque['conseil_sante'] ?? null,
                'conseils' => $risque['conseils'] ?? [],
                'justification' => $risque['justification'] ?? null,
                'profils_detectes' => $risque['profils_detectes'] ?? [],
                'facteurs' => $risque['facteurs'] ?? [],
                'score_qualite' => $risque['score_qualite'] ?? null,
                'score_profil' => $risque['score_profil'] ?? null,
                'produit' => $nomProduit,
                'auto_open' => (bool) session('risque_ia_popup'),
            ];
        @endphp
        <script type="application/json" id="nt-risk-result-data">{!! json_encode($risquePayload, JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
</article>
