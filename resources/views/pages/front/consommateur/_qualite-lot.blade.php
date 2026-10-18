@php
    $risque = session('risque_ia');
    $nomProduit = $lot->produit?->nom ?? 'ce produit';
@endphp

{{-- Grande carte produit + bouton chat IA --}}
<article class="nt-q-hero" aria-labelledby="nt-q-lot-name">
    <div class="nt-q-hero__glow" aria-hidden="true"></div>
    <div class="nt-q-hero__content">
        <p class="nt-q-hero__cat">{{ $lot->produit?->categorie?->nom ?? 'Produit' }}</p>
        <h2 id="nt-q-lot-name" class="nt-q-hero__name">{{ $lot->produit?->nom }}</h2>
        <p class="nt-q-hero__ref">
            <i class="bi bi-upc" aria-hidden="true"></i>
            {{ $lot->numero_lot }}
        </p>
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
                'justification' => $risque['justification'] ?? null,
                'produit' => $nomProduit,
                'auto_open' => (bool) session('risque_ia_popup'),
            ];
        @endphp
        <script type="application/json" id="nt-risk-result-data">{!! json_encode($risquePayload, JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
</article>
