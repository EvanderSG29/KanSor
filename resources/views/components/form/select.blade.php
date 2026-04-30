@props([
    'name',
    'label',
    'id' => null,
    'required' => false,
    'help' => null,
    'wrapperClass' => null,
])

@php
    $fieldId = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorKey = trim((string) preg_replace('/\[(.*?)\]/', '.$1', $name), '.');
@endphp

<div @class(['form-group', $wrapperClass])>
    <label for="{{ $fieldId }}">{{ $label }}</label>
    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @required($required)
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($errorKey)]) }}
    >
        {{ $slot }}
    </select>

    @if ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif

    @error($errorKey)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
