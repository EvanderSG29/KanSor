@props([
    'title' => 'Belum ada data',
    'message' => 'Data akan ditampilkan setelah tersedia.',
    'icon' => 'fas fa-inbox',
    'compact' => false,
])

<div {{ $attributes->class([
    'd-flex flex-column align-items-center justify-content-center text-center text-muted',
    'px-4',
    'py-4' => $compact,
    'py-5' => ! $compact,
]) }}>
    <div class="{{ $compact ? 'mb-2' : 'mb-3' }}">
        <i class="{{ $icon }} {{ $compact ? 'fa-lg' : 'fa-2x' }}"></i>
    </div>
    <div class="font-weight-semibold {{ $compact ? 'mb-1' : 'mb-2' }}">{{ $title }}</div>
    <p class="mb-0 {{ $compact ? 'small' : '' }}">{{ $message }}</p>
</div>
