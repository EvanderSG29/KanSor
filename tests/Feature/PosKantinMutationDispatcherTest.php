<?php

use App\Jobs\SyncPosKantinMutationJob;
use App\Services\PosKantin\PosKantinMutationDispatcher;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('dispatcher queues supplier sync when apps script is configured', function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
    ]);
    Queue::fake();

    $result = app(PosKantinMutationDispatcher::class)->dispatch('saveSupplier', [
        ['supplierName' => 'Supplier Baru'],
    ]);

    expect($result['status'])->toBe('queued')
        ->and($result['message'])->toContain('masuk antrean');

    Queue::assertPushed(SyncPosKantinMutationJob::class, function (SyncPosKantinMutationJob $job): bool {
        return $job->method === 'saveSupplier'
            && ($job->arguments[0]['supplierName'] ?? null) === 'Supplier Baru';
    });
});

test('dispatcher queues food sync when apps script is configured', function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
    ]);
    Queue::fake();

    $result = app(PosKantinMutationDispatcher::class)->dispatch('saveFood', [
        ['name' => 'Bakwan'],
    ]);

    expect($result['status'])->toBe('queued')
        ->and($result['message'])->toContain('masuk antrean');

    Queue::assertPushed(SyncPosKantinMutationJob::class, function (SyncPosKantinMutationJob $job): bool {
        return $job->method === 'saveFood'
            && ($job->arguments[0]['name'] ?? null) === 'Bakwan';
    });
});

test('dispatcher queues transaction sync when apps script is configured', function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
    ]);
    Queue::fake();

    $result = app(PosKantinMutationDispatcher::class)->dispatch('saveTransaction', [
        ['transactionDate' => '2026-04-29', 'foodId' => '1'],
    ]);

    expect($result['status'])->toBe('queued')
        ->and($result['message'])->toContain('masuk antrean');

    Queue::assertPushed(SyncPosKantinMutationJob::class, function (SyncPosKantinMutationJob $job): bool {
        return $job->method === 'saveTransaction'
            && ($job->arguments[0]['transactionDate'] ?? null) === '2026-04-29'
            && ($job->arguments[0]['foodId'] ?? null) === '1';
    });
});

test('dispatcher still warns for legacy local mutation endpoints that are not yet compatible with apps script', function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
    ]);

    $result = app(PosKantinMutationDispatcher::class)->dispatch('createTransaction', [
        ['transactionDate' => '2026-04-29'],
    ]);

    expect($result['status'])->toBe('unsupported')
        ->and($result['message'])->toContain('belum tersinkron ke spreadsheet');
});

test('dispatcher queues user sync when apps script is configured', function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
    ]);
    Queue::fake();

    $result = app(PosKantinMutationDispatcher::class)->dispatch('saveUser', [
        ['fullName' => 'Petugas Baru'],
    ]);

    expect($result['status'])->toBe('queued')
        ->and($result['message'])->toContain('masuk antrean');

    Queue::assertPushed(SyncPosKantinMutationJob::class, function (SyncPosKantinMutationJob $job): bool {
        return $job->method === 'saveUser'
            && ($job->arguments[0]['fullName'] ?? null) === 'Petugas Baru';
    });
});

test('dispatcher warns when pos kantin api is not configured', function () {
    config([
        'services.pos_kantin.api_url' => null,
    ]);

    $result = app(PosKantinMutationDispatcher::class)->dispatch('saveSupplier', [
        ['name' => 'Supplier Baru'],
    ]);

    expect($result['status'])->toBe('failed')
        ->and($result['message'])->toContain('belum dikonfigurasi');
});

test('dispatcher reports applied when sync queue runs mutation immediately', function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
        'services.pos_kantin.admin_email' => 'evandersmidgidiin@gmail.com',
        'services.pos_kantin.admin_password' => 'secret-password',
        'services.pos_kantin.timeout' => 20,
        'services.pos_kantin.connect_timeout' => 10,
        'queue.default' => 'sync',
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            return match ($request['action'] ?? null) {
                'login' => Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'service-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]),
                'saveSupplier' => Http::response([
                    'success' => true,
                    'message' => 'Supplier berhasil disimpan.',
                    'data' => [
                        'id' => 'SUP-NEW',
                    ],
                ]),
            };
        },
    ]);

    $result = app(PosKantinMutationDispatcher::class)->dispatch('saveSupplier', [[
        'supplierName' => 'Supplier Baru',
        'contactName' => '',
        'contactPhone' => '08123',
        'commissionRate' => 10,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 0,
        'notes' => '',
        'isActive' => true,
    ]]);

    expect($result['status'])->toBe('applied')
        ->and($result['message'])->toContain('langsung diterapkan');
});

test('dispatcher reports failed when sync queue execution throws an exception', function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
        'services.pos_kantin.admin_email' => 'evandersmidgidiin@gmail.com',
        'services.pos_kantin.admin_password' => 'secret-password',
        'services.pos_kantin.timeout' => 20,
        'services.pos_kantin.connect_timeout' => 10,
        'queue.default' => 'sync',
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            return match ($request['action'] ?? null) {
                'login' => Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'service-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]),
                'saveSupplier' => Http::response([
                    'success' => false,
                    'message' => 'Apps Script gagal.',
                    'data' => null,
                ]),
            };
        },
    ]);

    $result = app(PosKantinMutationDispatcher::class)->dispatch('saveSupplier', [[
        'supplierName' => 'Supplier Baru',
        'contactName' => '',
        'contactPhone' => '08123',
        'commissionRate' => 10,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 0,
        'notes' => '',
        'isActive' => true,
    ]]);

    expect($result['status'])->toBe('failed')
        ->and($result['message'])->toContain('gagal dijalankan');
});
