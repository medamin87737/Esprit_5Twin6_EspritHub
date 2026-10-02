@props([
    'name',
    'label',
    'accept' => null,
    'help' => null,
])

@php($id = $attributes->get('id', $name))

<div class="form-group">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="custom-file">
        <input type="file" id="{{ $id }}" name="{{ $name }}" @if ($accept) accept="{{ $accept }}" @endif
               {{ $attributes->except('id')->class(['custom-file-input', 'is-invalid' => $errors->has($name)]) }}
               onchange="this.nextElementSibling.textContent = this.files.length ? this.files[0].name : 'Choisir un fichier…'">
        <label class="custom-file-label text-muted font-weight-normal" for="{{ $id }}">Choisir un fichier…</label>
    </div>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        @if ($help)
            <small class="form-text">{{ $help }}</small>
        @endif
    @enderror
</div>
