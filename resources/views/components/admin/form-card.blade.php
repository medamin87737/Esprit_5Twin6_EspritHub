@props([
    'action' => null,
    'method' => 'POST',
    'title',
    'cancel',
    'submitLabel' => 'Enregistrer',
    'files' => false,
])

<form method="POST" action="{{ $action ?? url()->current() }}" @if ($files) enctype="multipart/form-data" @endif novalidate>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div {{ $attributes->class(['card mb-4']) }}>
        <div class="card-header">
            <h2 class="nt-card-title">{{ $title }}</h2>
        </div>

        <div class="card-body">
            {{ $slot }}
        </div>

        <div class="card-footer nt-form-footer">
            <span class="nt-form-hint">
                @if ($action)
                    <i class="bi bi-asterisk text-danger" style="font-size: 0.55rem;" aria-hidden="true"></i> Champs obligatoires
                @else
                    <i class="bi bi-info-circle" aria-hidden="true"></i> L'enregistrement sera actif dès que le contrôleur du module sera branché.
                @endif
            </span>
            <div class="d-flex" style="gap: 0.5rem;">
                <a href="{{ $cancel }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-primary" @disabled(! $action)>
                    <i class="bi bi-check2 mr-1" aria-hidden="true"></i> {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</form>
