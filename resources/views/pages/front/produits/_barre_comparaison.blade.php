{{--
    _barre_comparaison.blade.php — Module 1 (Ghada)
    Barre flottante affichant le nombre de produits sélectionnés pour la comparaison.
    Incluse dans index.blade.php et show.blade.php.
    La session est lue côté serveur ; le JS la met à jour sans rechargement.
--}}
@php
    $selectionComparer = session('m1_comparaison', []);
    $nbSelection = count($selectionComparer);
    $max = auth()->check() && auth()->user()->can('comparer-sans-limite')
        ? (int) config('nutritrace.comparaison.connecte')
        : (int) config('nutritrace.comparaison.visiteur');
@endphp

<div id="m1-barre-comparaison"
     class="m1-barre-comparaison {{ $nbSelection > 0 ? 'm1-barre-comparaison--visible' : '' }}"
     role="status"
     aria-live="polite"
     data-max="{{ $max }}"
     data-nb="{{ $nbSelection }}">
    <div class="container d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <span class="m1-barre-comparaison__label">
            <i class="bi bi-bar-chart-steps me-2" aria-hidden="true"></i>
            <strong id="m1-barre-nb">{{ $nbSelection }}</strong>&nbsp;/&nbsp;{{ $max }}
            produit{{ $nbSelection > 1 ? 's' : '' }} sélectionné{{ $nbSelection > 1 ? 's' : '' }}
        </span>
        <div class="d-flex gap-2">
            <a href="{{ route('front.produits.comparer') }}"
               id="m1-btn-voir-comparaison"
               class="btn btn-sm btn-primary {{ $nbSelection < 2 ? 'disabled' : '' }}"
               aria-disabled="{{ $nbSelection < 2 ? 'true' : 'false' }}">
                <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>Comparer
            </a>
            <form method="POST" action="{{ route('front.produits.comparaison.vider') }}" class="m1-form-vider">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="btn btn-sm btn-outline-light"
                        aria-label="Vider la sélection">
                    <i class="bi bi-trash me-1" aria-hidden="true"></i>Vider
                </button>
            </form>
        </div>
    </div>
</div>
