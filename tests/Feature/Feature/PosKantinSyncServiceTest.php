<?php

use App\Models\PosKantinDeviceCredential;
use App\Models\PosKantinSyncConflict;
use App\Models\PosKantinSyncOutbox;
use App\Models\PosKantinSyncRun;
use App\Models\User;
use App\Services\PosKantin\PosKantinLocalStore;
use App\Services\PosKantin\PosKantinSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
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

test('sync status exposes queued applied failed and latest push summary', function () {
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
        'last_remote_sync_at' => now(),
    ]);

    PosKantinSyncOutbox::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'client_mutation_id' => '11111111-1111-1111-1111-111111111111',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-001',
        'payload' => ['id' => 'SUP-001'],
        'status' => 'pending',
    ]);

    PosKantinSyncOutbox::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'client_mutation_id' => '22222222-2222-2222-2222-222222222222',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-002',
        'payload' => ['id' => 'SUP-002'],
        'status' => 'applied',
    ]);

    PosKantinSyncOutbox::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'client_mutation_id' => '33333333-3333-3333-3333-333333333333',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-003',
        'payload' => ['id' => 'SUP-003'],
        'status' => 'failed',
    ]);

    PosKantinSyncConflict::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'outbox_id' => PosKantinSyncOutbox::query()
            ->where('client_mutation_id', '33333333-3333-3333-3333-333333333333')
            ->value('id'),
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-003',
        'local_payload' => ['id' => 'SUP-003'],
        'server_payload' => ['id' => 'SUP-003'],
        'resolution_status' => 'unresolved',
    ]);

    PosKantinSyncRun::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'trigger' => 'manual',
        'status' => 'success',
        'started_at' => now()->subMinute(),
        'ended_at' => now(),
        'summary' => [
            'push' => [
                'queued' => 2,
                'applied' => 1,
                'failed' => 1,
                'conflicts' => 1,
            ],
            'pull' => [],
        ],
    ]);

    $status = app(PosKantinSyncService::class)->statusForUser($user);

    expect($status['queuedCount'])->toBe(1)
        ->and($status['pendingCount'])->toBe(1)
        ->and($status['appliedCount'])->toBe(1)
        ->and($status['failedCount'])->toBe(1)
        ->and($status['conflictCount'])->toBe(1)
        ->and($status['lastRun']['summary']['push']['applied'] ?? null)->toBe(1);
});

test('unresolved conflicts expose field diff context for the sync page', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    $outbox = PosKantinSyncOutbox::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'client_mutation_id' => '44444444-4444-4444-4444-444444444444',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-004',
        'payload' => [
            'id' => 'SUP-004',
            'supplierName' => 'Supplier Lokal',
            'updatedAt' => '2026-04-29T08:00:00.000Z',
        ],
        'expected_updated_at' => '2026-04-29T08:00:00.000Z',
        'status' => 'conflict',
        'last_error' => 'Data server berubah sejak perubahan lokal dibuat.',
        'server_snapshot' => [
            'id' => 'SUP-004',
            'supplierName' => 'Supplier Server',
            'updatedAt' => '2026-04-29T09:15:00.000Z',
        ],
    ]);

    PosKantinSyncConflict::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'outbox_id' => $outbox->id,
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-004',
        'local_payload' => [
            'id' => 'SUP-004',
            'supplierName' => 'Supplier Lokal',
            'updatedAt' => '2026-04-29T08:00:00.000Z',
        ],
        'server_payload' => [
            'id' => 'SUP-004',
            'supplierName' => 'Supplier Server',
            'updatedAt' => '2026-04-29T09:15:00.000Z',
        ],
        'resolution_status' => 'unresolved',
    ]);

    $conflicts = app(PosKantinSyncService::class)->unresolvedConflicts($user);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['entityLabel'])->toBe('Supplier')
        ->and($conflicts[0]['lastError'])->toBe('Data server berubah sejak perubahan lokal dibuat.')
        ->and($conflicts[0]['localUpdatedAt'])->toBe('2026-04-29T08:00:00.000Z')
        ->and($conflicts[0]['serverUpdatedAt'])->toBe('2026-04-29T09:15:00.000Z')
        ->and($conflicts[0]['hasComparisonContext'])->toBeTrue()
        ->and($conflicts[0]['fieldDiffs'])->toBe([
            [
                'field' => 'supplierName',
                'localValue' => 'Supplier Lokal',
                'serverValue' => 'Supplier Server',
            ],
            [
                'field' => 'updatedAt',
                'localValue' => '2026-04-29T08:00:00.000Z',
                'serverValue' => '2026-04-29T09:15:00.000Z',
            ],
        ]);
});

test('sync payload columns are stored encrypted for new records', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    $outbox = PosKantinSyncOutbox::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'client_mutation_id' => '66666666-6666-6666-6666-666666666666',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-006',
        'payload' => [
            'id' => 'SUP-006',
            'supplierName' => 'Supplier Rahasia',
        ],
        'status' => 'pending',
        'server_snapshot' => [
            'id' => 'SUP-006',
            'supplierName' => 'Supplier Server',
        ],
    ]);

    $conflict = PosKantinSyncConflict::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'outbox_id' => $outbox->id,
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-006',
        'local_payload' => [
            'id' => 'SUP-006',
            'supplierName' => 'Supplier Rahasia',
        ],
        'server_payload' => [
            'id' => 'SUP-006',
            'supplierName' => 'Supplier Server',
        ],
        'resolution_status' => 'unresolved',
    ]);

    $rawOutboxPayload = DB::table('pos_sync_outbox')->where('id', $outbox->id)->value('payload');
    $rawConflictPayload = DB::table('pos_sync_conflicts')->where('id', $conflict->id)->value('local_payload');

    expect($rawOutboxPayload)->toBeString()
        ->not->toContain('Supplier Rahasia')
        ->and($rawConflictPayload)->toBeString()
        ->not->toContain('Supplier Rahasia')
        ->and($outbox->fresh()?->payload)->toMatchArray([
            'id' => 'SUP-006',
            'supplierName' => 'Supplier Rahasia',
        ])
        ->and($conflict->fresh()?->local_payload)->toMatchArray([
            'id' => 'SUP-006',
            'supplierName' => 'Supplier Rahasia',
        ]);
});

test('sync payload encryption migration converts legacy plaintext rows', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    $outboxId = DB::table('pos_sync_outbox')->insertGetId([
        'scope_owner_user_id' => $user->getKey(),
        'client_mutation_id' => '77777777-7777-7777-7777-777777777777',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-007',
        'payload' => json_encode([
            'id' => 'SUP-007',
            'supplierName' => 'Supplier Plaintext',
        ], JSON_THROW_ON_ERROR),
        'status' => 'conflict',
        'server_snapshot' => json_encode([
            'id' => 'SUP-007',
            'supplierName' => 'Supplier Server',
        ], JSON_THROW_ON_ERROR),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $conflictId = DB::table('pos_sync_conflicts')->insertGetId([
        'scope_owner_user_id' => $user->getKey(),
        'outbox_id' => $outboxId,
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-007',
        'local_payload' => json_encode([
            'id' => 'SUP-007',
            'supplierName' => 'Supplier Plaintext',
        ], JSON_THROW_ON_ERROR),
        'server_payload' => json_encode([
            'id' => 'SUP-007',
            'supplierName' => 'Supplier Server',
        ], JSON_THROW_ON_ERROR),
        'resolution_status' => 'unresolved',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_04_30_051927_encrypt_pos_kantin_sync_payloads.php');
    $migration->up();

    $rawOutboxPayload = DB::table('pos_sync_outbox')->where('id', $outboxId)->value('payload');
    $rawConflictPayload = DB::table('pos_sync_conflicts')->where('id', $conflictId)->value('local_payload');

    expect($rawOutboxPayload)->toBeString()
        ->not->toContain('Supplier Plaintext')
        ->and($rawConflictPayload)->toBeString()
        ->not->toContain('Supplier Plaintext')
        ->and(PosKantinSyncOutbox::query()->findOrFail($outboxId)->payload)->toMatchArray([
            'id' => 'SUP-007',
            'supplierName' => 'Supplier Plaintext',
        ])
        ->and(PosKantinSyncConflict::query()->findOrFail($conflictId)->local_payload)->toMatchArray([
            'id' => 'SUP-007',
            'supplierName' => 'Supplier Plaintext',
        ]);
});
