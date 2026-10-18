@props([
    'name' => 'rapport',
    'label' => 'Rapport (TXT / DOCX)',
    'accept' => '.txt,.docx,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'help' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id', 'champ-'.str_replace(['[', ']', '.'], '-', $name));
@endphp

<div {{ $attributes->class(['form-field nt-drop-wrap']) }} data-nt-dropzone>
    <label class="form-label" for="{{ $id }}">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
    </label>

    <div class="nt-dropzone @error($name) is-invalid @enderror" data-nt-drop-surface>
        <input
            type="file"
            id="{{ $id }}"
            name="{{ $name }}"
            class="nt-dropzone__input"
            accept="{{ $accept }}"
            @required($required)
            data-nt-drop-input
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
            <div class="form-text">{{ $help }}</div>
        @endif
    @enderror
</div>
