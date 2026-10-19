{{-- Contenu du popup Qualité (chargé en AJAX dans la modale partagée) --}}
@php
    $verdict = match ($statut) {
        'conforme' => ['classe' => 'success', 'icone' => 'bi-shield-check', 'libelle' => 'Qualité conforme', 'texte' => 'Toutes les analyses décidées sont conformes.'],
        'non_conforme' => ['classe' => 'danger', 'icone' => 'bi-exclamation-octagon', 'libelle' => 'Non-conformité détectée', 'texte' => 'Au moins une analyse est non conforme : prudence.'],
        'en_attente' => ['classe' => 'warning', 'icone' => 'bi-hourglass-split', 'libelle' => 'Analyses en cours', 'texte' => 'Des résultats de laboratoire sont encore attendus.'],
        default => ['classe' => 'muted', 'icone' => 'bi-dash-circle', 'libelle' => 'Pas encore analysé', 'texte' => 'Aucune analyse qualité enregistrée pour ce produit.'],
    };
@endphp

@php
    $derniere = $dernieres->first();
    $derniereTexte = $derniere
        ? $derniere->typeLabel().' · '.$derniere->resultatLabel().' · '.($derniere->laboratoire?->nom ?? '').' · '.$derniere->date_prelevement?->format('d/m/Y')
        : '';
@endphp

<div class="nt-qp"
     data-produit="{{ $produit->nom }}"
     data-lot="{{ $lotPrincipal?->numero_lot }}"
     data-qualite-url="{{ $lotPrincipal ? route('consommateur.qualite.index', ['numero' => $lotPrincipal->numero_lot]) : '' }}"
     data-fiche-url="{{ route('front.produits.show', $produit) }}"
     data-verdict="{{ $verdict['libelle'] }}"
     data-verdict-texte="{{ $verdict['texte'] }}"
     data-total="{{ $total }}"
     data-conformes="{{ $conformes }}"
     data-non-conformes="{{ $nonConformes }}"
     data-en-attente="{{ $enAttente }}"
     data-pct="{{ $pctConformes ?? '' }}"
     data-derniere="{{ $derniereTexte }}">
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

    {{-- Assistant intelligent intégré --}}
    <div class="nt-qp__assistant">
        <div class="nt-qp__assistant-head">
            <span class="nt-qp__assistant-avatar"><i class="bi bi-stars" aria-hidden="true"></i></span>
            <div class="nt-qp__assistant-titres">
                <strong>Assistant NutriTrace</strong>
                <span><span class="nt-qp__assistant-dot" aria-hidden="true"></span> En ligne — demandez-moi</span>
            </div>
            <i class="bi bi-chat-heart nt-qp__assistant-coeur" aria-hidden="true"></i>
        </div>

        <div class="nt-qp__assistant-log" data-assistant-log aria-live="polite">
            <div class="nt-msg nt-msg--bot">
                Bonjour ! Je connais la qualité de <strong>{{ $produit->nom }}</strong>.
                @if ($lotPrincipal)
                    Dites par exemple « <em>Amène-moi à mon espace qualité pour consulter</em> » et je vous y emmène (lot {{ $lotPrincipal->numero_lot }}).
                @else
                    Ce produit n'a pas encore de lot : je peux vous expliquer son statut qualité.
                @endif
            </div>
        </div>

        <div class="nt-qp__chips">
            @if ($lotPrincipal)
                <button type="button" class="nt-qp__chip" data-assistant-question="Tu peux m'amener à mon espace qualité pour consulter ?">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Espace qualité
                </button>
            @endif
            <button type="button" class="nt-qp__chip" data-assistant-question="Ce produit est-il conforme ?">
                <i class="bi bi-shield-check" aria-hidden="true"></i> Est-il conforme ?
            </button>
            <button type="button" class="nt-qp__chip" data-assistant-question="Quelle est la dernière analyse ?">
                <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Dernière analyse
            </button>
        </div>

        <form class="nt-qp__assistant-form" data-assistant-form>
            <label class="visually-hidden" for="nt-assistant-input-{{ $produit->id }}">Écrire à l'assistant qualité</label>
            <input type="text" id="nt-assistant-input-{{ $produit->id }}" data-assistant-input class="nt-qp__assistant-input"
                   placeholder="Ex. Tu peux m'amener à mon espace qualité ?" autocomplete="off" maxlength="300">
            <button type="submit" class="nt-qp__assistant-send" aria-label="Envoyer à l'assistant">
                <i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </form>
    </div>

    <div class="nt-qp__actions">
        @if ($lotPrincipal)
            <a href="{{ route('consommateur.qualite.index', ['numero' => $lotPrincipal->numero_lot]) }}" class="btn btn-primary">
                <i class="bi bi-clipboard2-pulse me-1" aria-hidden="true"></i> Espace Qualité complet ({{ $lotPrincipal->numero_lot }})
            </a>
        @else
            <button type="button" class="btn btn-primary" disabled title="Aucun lot enregistré pour ce produit">
                <i class="bi bi-clipboard2-pulse me-1" aria-hidden="true"></i> Aucun lot à consulter
            </button>
        @endif
        <a href="{{ route('front.produits.show', $produit) }}" class="btn btn-outline-secondary">Voir la fiche</a>
    </div>
</div>
