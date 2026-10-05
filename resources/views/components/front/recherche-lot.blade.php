@props([
    'valeur' => null,
    'grand' => true,
])

<form {{ $attributes->class(['lot-search']) }} method="GET" action="{{ route('front.lots.search') }}" role="search">
    <label for="numero-lot" class="form-label">Saisir ou scanner un numéro de lot</label>
    <div class="lot-search-row">
        <div class="field-icon flex-grow-1">
            <i class="bi bi-upc-scan" aria-hidden="true"></i>
            <input @class(['form-control', 'form-control-lg' => $grand]) id="numero-lot" type="search" name="numero" value="{{ $valeur }}"
                   placeholder="Ex. LOT-2026-0001" autocomplete="off" autocapitalize="characters" required>
        </div>
        <button type="button" @class(['btn btn-outline-primary', 'btn-lg' => $grand]) data-scan="#numero-lot" title="Scanner avec la caméra">
            <i class="bi bi-camera" aria-hidden="true"></i><span class="d-none d-sm-inline ms-2">Scanner</span>
        </button>
        <button type="submit" @class(['btn btn-primary', 'btn-lg' => $grand])>
            <i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline ms-2">Tracer</span>
        </button>
    </div>
</form>
