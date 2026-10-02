@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
    'icon' => null,
])

@php($id = $attributes->get('id', $name))

<div class="form-group">
    <label for="{{ $id }}">{{ $label }}@if ($required)<span class="nt-required" aria-hidden="true">*</span>@endif</label>
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           @required($required)
           {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
           @if ($help) aria-describedby="{{ $id }}-help" @endif>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        @if ($help)
            <small id="{{ $id }}-help" class="form-text">{{ $help }}</small>
        @endif
    @enderror
</div>
