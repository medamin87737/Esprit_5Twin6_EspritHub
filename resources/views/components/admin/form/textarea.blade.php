@props([
    'name',
    'label',
    'value' => null,
    'required' => false,
    'help' => null,
    'rows' => 4,
])

@php($id = $attributes->get('id', $name))

<div class="form-group">
    <label for="{{ $id }}">{{ $label }}@if ($required)<span class="nt-required" aria-hidden="true">*</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        @if ($help)
            <small class="form-text">{{ $help }}</small>
        @endif
    @enderror
</div>
