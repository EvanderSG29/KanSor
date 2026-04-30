@props([
    'status',
    'context' => 'default',
    'label' => null,
    'tone' => null,
    'uppercase' => false,
])

@php
    $normalizedStatus = strtolower(trim((string) $status));
    $badgeTone = $tone;
    $badgeLabel = $label;

    if ($badgeTone === null || $badgeLabel === null) {
        $maps = [
            'active' => [
                'aktif' => ['success', 'Aktif'],
                'active' => ['success', 'Aktif'],
                'nonaktif' => ['secondary', 'Nonaktif'],
                'inactive' => ['secondary', 'Nonaktif'],
            ],
            'role' => [
                'admin' => ['info', 'Admin'],
                'petugas' => ['primary', 'Petugas'],
            ],
            'sync-run' => [
                'success' => ['success', 'Success'],
                'failed' => ['danger', 'Failed'],
                'running' => ['secondary', 'Running'],
                'pending' => ['warning', 'Pending'],
                'queued' => ['warning', 'Queued'],
            ],
            'supplier-payment' => [
                'menunggu' => ['warning', 'Menunggu'],
                'dibayar' => ['success', 'Dibayar'],
            ],
            'canteen-deposit' => [
                'menunggu' => ['warning', 'Menunggu'],
                'disetor' => ['success', 'Disetor'],
            ],
            'sale-lock' => [
                'locked' => ['success', 'Terkunci'],
                'open' => ['warning', 'Perlu Konfirmasi'],
            ],
            'default' => [
                'aktif' => ['success', 'Aktif'],
                'nonaktif' => ['secondary', 'Nonaktif'],
                'success' => ['success', 'Success'],
                'failed' => ['danger', 'Failed'],
                'warning' => ['warning', 'Warning'],
            ],
        ];

        [$mappedTone, $mappedLabel] = $maps[$context][$normalizedStatus]
            ?? $maps['default'][$normalizedStatus]
            ?? ['secondary', ucfirst($normalizedStatus !== '' ? $normalizedStatus : 'status')];

        $badgeTone ??= $mappedTone;
        $badgeLabel ??= $mappedLabel;
    }

    $renderedLabel = $uppercase ? strtoupper($badgeLabel) : $badgeLabel;
@endphp

<span {{ $attributes->class(['badge', 'badge-'.$badgeTone]) }}>
    {{ $renderedLabel }}
</span>
