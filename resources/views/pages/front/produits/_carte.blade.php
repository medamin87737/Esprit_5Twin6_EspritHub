{{--
    _carte.blade.php — Module 1 (Ghada)
    Clone enrichi de components/front/produit-card.blade.php.
    Ajoute le bouton « + Comparer » sans modifier le composant partagé.
    Variables : $produit (Produit avec relation categorie + certifications chargées).
--}}
@php
    /* Sélection en session pour le comparateur */
    $selectionComparer = session('m1_comparaison', []);
    $dejaSelectionne   = in_array($produit->id, $selectionComparer, true);
    $max = auth()->check() && auth()->user()->can('comparer-sans-limite')
        ? (int) config('nutritrace.comparaison.connecte')
        : (int) config('nutritrace.comparaison.visiteur');
    $limitePleineEtPasDedans = count($selectionComparer) >= $max && ! $dejaSelectionne;
@endphp

<article class="product-card h-100 m1-carte-produit {{ $dejaSelectionne ? 'm1-carte-produit--selectionnee' : '' }}"
         data-produit-id="{{ $produit->id }}">
    <div class="product-card-media">
        @if ($produit->image)
            <img src="{{ asset('storage/' . $produit->image) }}" alt="{{ $produit->nom }}" loading="lazy">
        @else
            <span class="product-card-placeholder"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
        @endif
        @if ($produit->categorie)
            <span class="label-chip product-card-chip">{{ $produit->categorie->nom }}</span>
        @endif
        @if ($score = $produit->scoreGlobal())
            <x-front.score-badge :score="$score" taille="sm" class="product-card-score" />
        @endif
    </div>
    <div class="product-card-body">
        <h2 class="product-card-title">{{ $produit->nom }}</h2>
        <p class="product-card-meta"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $produit->origine }}</p>
        @if ($produit->relationLoaded('certifications') && $produit->certifications->isNotEmpty())
            <div class="product-card-labels">
                @foreach ($produit->certifications->take(3) as $certification)
                    <x-front.certification-badge :certification="$certification" />
                @endforeach
            </div>
        @endif

        {{-- Bouton Comparer --}}
        <div class="m1-carte-comparer mt-2">
            @if ($dejaSelectionne)
                <form method="POST"
                      action="{{ route('front.produits.comparaison.retirer', $produit->id) }}"
                      class="m1-form-comparer"
                      data-ajax="true">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="btn btn-sm btn-success w-100 m1-btn-comparer m1-btn-comparer--retirer"
                            aria-label="Retirer {{ $produit->nom }} de la comparaison">
                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Dans la comparaison
                    </button>
                </form>
            @elseif (! $limitePleineEtPasDedans)
                <form method="POST"
                      action="{{ route('front.produits.comparaison.ajouter') }}"
                      class="m1-form-comparer"
                      data-ajax="true">
                    @csrf
                    <input type="hidden" name="produit_id" value="{{ $produit->id }}">
                    <button type="submit"
                            class="btn btn-sm btn-outline-primary w-100 m1-btn-comparer"
                            aria-label="Ajouter {{ $produit->nom }} à la comparaison">
                        <i class="bi bi-bar-chart-steps me-1" aria-hidden="true"></i>Comparer
                    </button>
                </form>
            @else
                <button type="button"
                        class="btn btn-sm btn-outline-secondary w-100"
                        disabled
                        title="Limite de {{ $max }} produits atteinte">
                    <i class="bi bi-lock me-1" aria-hidden="true"></i>Limite atteinte
                </button>
            @endif
        </div>

        <a href="{{ route('front.produits.show', $produit) }}"
           class="stretched-link product-card-link mt-2 d-block">
            Voir la fiche <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</article>
