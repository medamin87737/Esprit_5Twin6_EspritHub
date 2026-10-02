@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => 'Sélectionner…',
    'required' => false,
    'help' => null,
    'emptyMessage' => null,
])

@php
    $id = $attributes->get('id', $name);
    $selected = (string) old($name, $value);
    $options = collect($options);
@endphp

<div class="form-group">
    <label for="{{ $id }}">{{ $label }}@if ($required)<span class="nt-required" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
            {{ $attributes->except('id')->class(['custom-select', 'is-invalid' => $errors->has($name)]) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        @if ($options->isEmpty() && $emptyMessage)
            <small class="form-text text-warning"><i class="bi bi-exclamation-circle mr-1" aria-hidden="true"></i>{{ $emptyMessage }}</small>
        @elseif ($help)
            <small class="form-text">{{ $help }}</small>
        @endif
    @enderror
</div>
