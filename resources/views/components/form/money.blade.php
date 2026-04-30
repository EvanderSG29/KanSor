@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'help' => null,
    'wrapperClass' => null,
])

@php
    $fieldId = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorKey = trim((string) preg_replace('/\[(.*?)\]/', '.$1', $name), '.');
@endphp

<div @class(['form-group', $wrapperClass])>
    <label for="{{ $fieldId }}">{{ $label }}</label>
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text">Rp</span>
        </div>
        <input
            id="{{ $fieldId }}"
            type="text"
            name="{{ $name }}"
            value="{{ old($errorKey, $value) }}"
            inputmode="numeric"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @required($required)
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($errorKey)]) }}
        >
    </div>

    @if ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
