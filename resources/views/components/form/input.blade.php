@props([
    'name',
    'label',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'help' => null,
    'inputmode' => null,
    'wrapperClass' => null,
])

@php
    $fieldId = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorKey = trim((string) preg_replace('/\[(.*?)\]/', '.$1', $name), '.');
@endphp

<div @class(['form-group', $wrapperClass])>
    <label for="{{ $fieldId }}">{{ $label }}</label>
    <input
        id="{{ $fieldId }}"
        type="{{ $type }}"
        name="{{ $name }}"
        @if (! in_array($type, ['password', 'file'], true))
            value="{{ old($errorKey, $value) }}"
        @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @required($required)
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($errorKey)]) }}
    >

    @if ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif

    @error($errorKey)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
