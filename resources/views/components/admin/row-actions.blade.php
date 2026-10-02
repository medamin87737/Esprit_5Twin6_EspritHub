@props([
    'show' => null,
    'edit' => null,
    'delete' => null,
    'confirm' => 'Supprimer cet enregistrement ? Cette action est irréversible.',
])

<div class="nt-actions">
    @if ($show)
        <a href="{{ $show }}" class="nt-action" data-toggle="tooltip" title="Voir le détail">
            <i class="bi bi-eye" aria-hidden="true"></i><span class="sr-only">Voir</span>
        </a>
    @endif
    @if ($edit)
        <a href="{{ $edit }}" class="nt-action" data-toggle="tooltip" title="Modifier">
            <i class="bi bi-pencil" aria-hidden="true"></i><span class="sr-only">Modifier</span>
        </a>
    @endif
    @if ($delete)
        <form method="POST" action="{{ $delete }}" class="d-inline m-0" onsubmit="return confirm(@js($confirm));">
            @csrf
            @method('DELETE')
            <button type="submit" class="nt-action nt-action-danger" data-toggle="tooltip" title="Supprimer">
                <i class="bi bi-trash3" aria-hidden="true"></i><span class="sr-only">Supprimer</span>
            </button>
        </form>
    @endif
</div>
