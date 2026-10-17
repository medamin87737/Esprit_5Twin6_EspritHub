{{--
    _assistant_ia.blade.php — Module 1 (Ghada)
    Bloc « Assistant IA » inclus dans les formulaires admin ET pro.
    Utilise @pushOnce pour charger module1.js une seule fois même si inclus plusieurs fois.

    Variables attendues (toutes optionnelles avec valeurs par défaut) :
    - $urlCategoriser : route POST IA catégorisation
    - $urlOcr         : route POST IA OCR
--}}
@pushOnce('scripts')
    @vite('resources/assets/front/js/module1.js')
@endPushOnce

@php
    $urlCategoriser = $urlCategoriser ?? (auth()->check() ? route('ia.produits.categoriser') : null);
    $urlOcr         = $urlOcr ?? (auth()->check() ? route('ia.produits.ocr') : null);
@endphp

@if ($urlCategoriser || $urlOcr)
<div class="m1-assistant-ia" id="m1-assistant-ia">
    <p class="m1-assistant-ia__titre">
        <i class="bi bi-robot" aria-hidden="true"></i>
        Assistant IA — Suggestions automatiques
    </p>

    <div class="m1-assistant-ia__actions">
        @if ($urlCategoriser)
            <button type="button"
                    id="m1-ia-btn-categoriser"
                    class="btn btn-sm btn-outline-primary"
                    data-url="{{ $urlCategoriser }}"
                    aria-label="Suggérer la catégorie et la composition via l'IA">
                <i class="bi bi-tags me-1" aria-hidden="true"></i>Suggérer catégorie et composition
            </button>
        @endif

        @if ($urlOcr)
            <button type="button"
                    id="m1-ia-btn-ocr"
                    class="btn btn-sm btn-outline-secondary"
                    data-url="{{ $urlOcr }}"
                    aria-label="Lire une étiquette produit par photo">
                <i class="bi bi-camera me-1" aria-hidden="true"></i>Lire étiquette
            </button>
            {{-- Input fichier sans attribut name : jamais soumis avec le formulaire --}}
            <input type="file"
                   id="m1-ia-fichier-ocr"
                   accept="image/jpeg,image/png,image/webp"
                   style="display:none"
                   aria-hidden="true">
        @endif
    </div>

    {{-- Résultat catégorisation --}}
    @if ($urlCategoriser)
        <div id="m1-ia-resultat-cat" class="m1-ia-resultat notice-panel" role="status" aria-live="polite">
            {{-- Rempli dynamiquement par module1.js --}}
        </div>
    @endif

    {{-- Bouton « Tout appliquer » (injecté par JS une fois le résultat visible) --}}
    <div id="m1-ia-tout-appliquer" style="display:none" class="mt-2">
        <button type="button" id="m1-ia-btn-tout-appliquer" class="btn btn-sm btn-success">
            <i class="bi bi-check-all me-1" aria-hidden="true"></i>Tout appliquer
        </button>
    </div>
</div>
@endif
