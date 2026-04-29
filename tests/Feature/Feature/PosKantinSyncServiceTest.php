<?php

use App\Models\PosKantinDeviceCredential;
use App\Models\PosKantinSyncConflict;
use App\Models\PosKantinSyncOutbox;
use App\Models\User;
use App\Services\PosKantin\PosKantinLocalStore;
use App\Services\PosKantin\PosKantinSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
        'services.pos_kantin.sync_interval_seconds' => 60,
    ]);

    Http::preventStrayRequests();
});

test('sync service stores initial pull into local mirrors', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'syncPull') {
                return Http::response([
                    'success' => true,
                    'message' => 'Sync pull berhasil.',
                    'data' => [
                        'users' => [[
                            'id' => 'USR-001',
                            'fullName' => 'Evander',
                            'nickname' => 'Evander',
                            'email' => 'evandersmidgidiin@gmail.com',
                            'role' => 'admin',
                            'status' => 'aktif',
                            'authUpdatedAt' => '2026-04-28T10:00:00.000Z',
                            'createdAt' => '2026-04-28T09:00:00.000Z',
                            'updatedAt' => '2026-04-28T10:00:00.000Z',
                        ]],
                        'buyers' => [],
                        'savings' => [],
                        'suppliers' => [[
                            'id' => 'SUP-001',
                            'supplierName' => 'Kang Latif',
                            'contactName' => 'Pak Latif',
                            'contactPhone' => '08123',
                            'commissionRate' => 10,
                            'commissionBaseType' => 'revenue',
                            'payoutTermDays' => 1,
                            'notes' => '',
                            'isActive' => true,
                            'createdAt' => '2026-04-28T09:00:00.000Z',
                            'updatedAt' => '2026-04-28T10:00:00.000Z',
                        ]],
                        'transactions' => [],
                        'dailyFinance' => [],
                        'changeEntries' => [],
                        'supplierPayouts' => [],
                        'cursors' => [
                            'users' => '2026-04-28T10:00:00.000Z',
                            'buyers' => '',
                            'savings' => '',
                            'suppliers' => '2026-04-28T10:00:00.000Z',
                            'transactions' => '',
                            'dailyFinance' => '',
                            'changeEntries' => '',
                            'supplierPayouts' => '',
                        ],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'OK',
                'data' => ['results' => []],
            ]);
        },
    ]);

    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    PosKantinDeviceCredential::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'remote_user_id' => 'USR-001',
        'email' => $user->email,
        'trusted_device_token' => 'trusted-token',
        'trusted_device_expires_at' => now()->addDays(30),
        'remote_session_token' => 'session-token',
        'remote_session_expires_at' => now()->addHour(),
    ]);

    $result = app(PosKantinSyncService::class)->sync($user, 'manual');

    expect($result['ok'])->toBeTrue()
        ->and(app(PosKantinLocalStore::class)->payloads($user, 'suppliers'))->toHaveCount(1)
        ->and(app(PosKantinLocalStore::class)->payloads($user, 'users'))->toHaveCount(1);
});

test('sync service records conflicts from sync push', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'syncPush') {
                return Http::response([
                    'success' => true,
                    'message' => 'Sync push berhasil diproses.',
                    'data' => [
                        'results' => [[
                            'clientMutationId' => '11111111-1111-1111-1111-111111111111',
                            'status' => 'conflict',
                            'message' => 'Data server berubah sejak perubahan lokal dibuat.',
                            'serverRecord' => [
                                'id' => 'SUP-001',
                                'supplierName' => 'Supplier Server',
                                'contactName' => 'Server',
                                'contactPhone' => '',
                                'commissionRate' => 10,
                                'commissionBaseType' => 'revenue',
                                'payoutTermDays' => 1,
                                'notes' => '',
                                'isActive' => true,
                                'createdAt' => '2026-04-28T09:00:00.000Z',
                                'updatedAt' => '2026-04-28T10:00:00.000Z',
                            ],
                        ]],
                    ],
                ]);
            }

            if (($request['action'] ?? null) === 'syncPull') {
                return Http::response([
                    'success' => true,
                    'message' => 'Sync pull berhasil.',
                    'data' => [
                        'users' => [],
                        'buyers' => [],
                        'savings' => [],
                        'suppliers' => [],
                        'transactions' => [],
                        'dailyFinance' => [],
                        'changeEntries' => [],
                        'supplierPayouts' => [],
                        'cursors' => [
                            'users' => '',
                            'buyers' => '',
                            'savings' => '',
                            'suppliers' => '',
                            'transactions' => '',
                            'dailyFinance' => '',
                            'changeEntries' => '',
                            'supplierPayouts' => '',
                        ],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'OK',
                'data' => ['results' => []],
            ]);
        },
    ]);

    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    PosKantinDeviceCredential::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'remote_user_id' => 'USR-001',
        'email' => $user->email,
        'trusted_device_token' => 'trusted-token',
        'trusted_device_expires_at' => now()->addDays(30),
        'remote_session_token' => 'session-token',
        'remote_session_expires_at' => now()->addHour(),
    ]);

    PosKantinSyncOutbox::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'client_mutation_id' => '11111111-1111-1111-1111-111111111111',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-001',
        'payload' => [
            'id' => 'SUP-001',
            'supplierName' => 'Supplier Local',
        ],
        'expected_updated_at' => '2026-04-28T09:00:00.000Z',
        'status' => 'pending',
    ]);

    $result = app(PosKantinSyncService::class)->sync($user, 'manual');

    expect($result['ok'])->toBeTrue()
        ->and(PosKantinSyncOutbox::query()->first()?->status)->toBe('conflict')
        ->and(PosKantinSyncConflict::query()->count())->toBe(1);
});
