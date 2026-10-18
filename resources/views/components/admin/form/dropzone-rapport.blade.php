@props([
    'name' => 'rapport',
    'label' => 'Rapport (TXT / DOCX)',
    'accept' => '.txt,.docx,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'help' => null,
])

@php($id = $attributes->get('id', $name))

<div class="form-group nt-drop-wrap" data-nt-dropzone>
    <label for="{{ $id }}">{{ $label }}</label>

    <div class="nt-dropzone @error($name) is-invalid @enderror" data-nt-drop-surface>
        <input
            type="file"
            id="{{ $id }}"
            name="{{ $name }}"
            class="nt-dropzone__input"
            accept="{{ $accept }}"
            data-nt-drop-input
            {{ $attributes->except('id') }}
        >

        <div class="nt-dropzone__ui" aria-hidden="true">
            <i class="bi bi-cloud-arrow-up nt-dropzone__icon"></i>
            <p class="nt-dropzone__title mb-1">Glissez-déposez le rapport ici</p>
            <p class="nt-dropzone__hint mb-2">ou cliquez pour parcourir · TXT ou DOCX · 5 Mo max</p>
            <span class="nt-dropzone__file" data-nt-drop-name>Aucun fichier sélectionné</span>
        </div>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        @if ($help)
            <small class="form-text">{{ $help }}</small>
        @endif
    @enderror
</div>
