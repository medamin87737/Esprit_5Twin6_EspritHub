@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'options' => [],
    'placeholder' => null,
    'help' => null,
    'required' => false,
    'rows' => 4,
])

@php
    $id = $attributes->get('id', 'champ-' . str_replace(['[', ']', '.'], '-', $name));
    $valeur = $type === 'file' ? null : old($name, $value);
    $classe = $type === 'select' ? 'form-select' : 'form-control';
@endphp

<div {{ $attributes->only('class')->class(['form-field']) }}>
    <label class="form-label" for="{{ $id }}">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
    </label>

    @if ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @class([$classe, 'is-invalid' => $errors->has($name)]) @required($required)
                {{ $attributes->except(['class', 'id']) }}>
            <option value="">{{ $placeholder ?? 'Choisir…' }}</option>
            @foreach ($options as $cle => $libelle)
                <option value="{{ $cle }}" @selected((string) $valeur === (string) $cle)>{{ $libelle }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @class([$classe, 'is-invalid' => $errors->has($name)])
                  placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except(['class', 'id']) }}>{{ $valeur }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @class([$classe, 'is-invalid' => $errors->has($name)])
               @if ($type !== 'file') value="{{ $valeur }}" @endif placeholder="{{ $placeholder }}" @required($required)
               {{ $attributes->except(['class', 'id']) }}>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @else
        @if ($help)
            <div class="form-text">{{ $help }}</div>
        @endif
    @enderror
</div>
