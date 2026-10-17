{{--
    _recommandations.blade.php — Module 1 (Ghada)
    Fragment HTML retourné par IaRecommandationsController en JSON.
    Variables : $recommandations (Collection de arrays {produit, score, similarite, gain_co2})
--}}
<div class="panel">
    <h2 class="panel-title">
        <i class="bi bi-stars text-success" aria-hidden="true"></i>
        Alternatives plus écologiques
    </h2>

    @if ($recommandations->isEmpty())
        <p class="text-muted small mb-0">Aucune alternative plus écologique trouvée pour ce produit.</p>
    @else
        <div class="m1-recommandations__grid">
            @foreach ($recommandations as $rec)
                @php $p = $rec['produit'] @endphp
                <a href="{{ route('front.produits.show', $p) }}"
                   class="product-card m1-recommandations__card"
                   style="text-decoration:none">
                    <div class="product-card-media" style="aspect-ratio: 16/10">
                        @if ($p->image)
                            <img src="{{ asset('storage/' . $p->image) }}" alt="{{ $p->nom }}" loading="lazy">
                        @else
                            <span class="product-card-placeholder"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                        @endif
                        @if ($score = $p->scoreGlobal())
                            <x-front.score-badge :score="$score" taille="sm" class="product-card-score" />
                        @endif
                    </div>
                    <div class="product-card-body p-2">
                        <p class="product-card-title mb-0" style="font-size:.95rem">{{ $p->nom }}</p>
                        <p class="product-card-meta mb-1" style="font-size:.8rem">
                            <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $p->origine }}
                        </p>
                        @if ($rec['gain_co2'] > 0)
                            <span class="status-pill status-valid" style="font-size:.72rem">
                                <i class="bi bi-leaf me-1" aria-hidden="true"></i>
                                -{{ number_format($rec['gain_co2'], 2, ',', ' ') }} kg CO₂e
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
