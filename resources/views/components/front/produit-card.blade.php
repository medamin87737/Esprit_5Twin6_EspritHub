@props(['produit'])

<article {{ $attributes->class(['product-card h-100']) }}>
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
                @foreach ($produit->certifications as $certification)
                    <x-front.certification-badge :certification="$certification" />
                @endforeach
            </div>
        @endif
        <a href="{{ route('front.produits.show', $produit) }}" class="stretched-link product-card-link">Voir la fiche <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
</article>
