{{-- Contenu du popup Qualité (chargé en AJAX dans la modale partagée) --}}
@php
    $verdict = match ($statut) {
        'conforme' => ['classe' => 'success', 'icone' => 'bi-shield-check', 'libelle' => 'Qualité conforme', 'texte' => 'Toutes les analyses décidées sont conformes.'],
        'non_conforme' => ['classe' => 'danger', 'icone' => 'bi-exclamation-octagon', 'libelle' => 'Non-conformité détectée', 'texte' => 'Au moins une analyse est non conforme : prudence.'],
        'en_attente' => ['classe' => 'warning', 'icone' => 'bi-hourglass-split', 'libelle' => 'Analyses en cours', 'texte' => 'Des résultats de laboratoire sont encore attendus.'],
        default => ['classe' => 'muted', 'icone' => 'bi-dash-circle', 'libelle' => 'Pas encore analysé', 'texte' => 'Aucune analyse qualité enregistrée pour ce produit.'],
    };
@endphp

<div class="nt-qp">
    <div class="nt-qp__head">
        <div>
            <p class="nt-qp__cat">{{ $produit->categorie?->nom ?? 'Produit' }} · {{ $produit->origine }}</p>
            <h3 class="nt-qp__name">{{ $produit->nom }}</h3>
        </div>
        @if ($score)
            <x-front.score-badge :score="$score" taille="sm" />
        @endif
    </div>

    <div class="nt-qp__verdict nt-qp__verdict--{{ $verdict['classe'] }}" role="status">
        <i class="bi {{ $verdict['icone'] }}" aria-hidden="true"></i>
        <div>
            <strong>{{ $verdict['libelle'] }}</strong>
            <span>{{ $verdict['texte'] }}</span>
        </div>
    </div>

    <div class="nt-qp__stats">
        <div class="nt-qp__stat">
            <span class="nt-qp__stat-val">{{ $total }}</span>
            <span class="nt-qp__stat-lib">Analyse{{ $total > 1 ? 's' : '' }}</span>
        </div>
        <div class="nt-qp__stat">
            <span class="nt-qp__stat-val">{{ $pctConformes !== null ? $pctConformes.' %' : '—' }}</span>
            <span class="nt-qp__stat-lib">Conformes</span>
        </div>
        <div class="nt-qp__stat">
            <span class="nt-qp__stat-val">{{ $nbLotsControles }}/{{ $nbLots }}</span>
            <span class="nt-qp__stat-lib">Lots contrôlés</span>
        </div>
        <div class="nt-qp__stat">
            <span class="nt-qp__stat-val">{{ $enAttente }}</span>
            <span class="nt-qp__stat-lib">En attente</span>
        </div>
    </div>

    @if ($pctConformes !== null)
        <div class="nt-qp__bar" role="progressbar" aria-valuenow="{{ $pctConformes }}" aria-valuemin="0" aria-valuemax="100"
             aria-label="{{ $pctConformes }} % d'analyses conformes">
            <span style="width: {{ $pctConformes }}%"></span>
        </div>
    @endif

    <h4 class="nt-qp__subtitle"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Dernières analyses</h4>
    @forelse ($dernieres as $analyse)
        <div class="nt-qp__analyse">
            <span class="nt-qp__analyse-icone"><i class="bi {{ $analyse->icone() }}" aria-hidden="true"></i></span>
            <div class="nt-qp__analyse-main">
                <strong>{{ $analyse->typeLabel() }}</strong>
                <span>{{ $analyse->lot?->numero_lot }} · {{ $analyse->laboratoire?->nom ?? 'Laboratoire' }} · {{ $analyse->date_prelevement?->format('d/m/Y') }}</span>
            </div>
            <x-front.analyses-badge :synthese="$analyse->resultat" />
        </div>
    @empty
        <p class="nt-qp__vide">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Aucun contrôle laboratoire pour le moment. Les résultats apparaîtront ici dès le premier prélèvement.
        </p>
    @endforelse

    <div class="nt-qp__actions">
        <a href="{{ route('consommateur.qualite.index') }}" class="btn btn-primary">
            <i class="bi bi-clipboard2-pulse me-1" aria-hidden="true"></i> Espace Qualité complet
        </a>
        <a href="{{ route('front.produits.show', $produit) }}" class="btn btn-outline-secondary">Voir la fiche</a>
    </div>
</div>
