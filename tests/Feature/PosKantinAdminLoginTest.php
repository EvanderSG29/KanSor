<?php

use App\Exceptions\PosKantinException;
use App\Models\PosKantinDeviceCredential;
use App\Models\User;
use App\Services\PosKantin\PosKantinSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
        'services.pos_kantin.device_label' => 'KanSor Test Device',
        'services.pos_kantin.offline_login_days' => 30,
    ]);

    Http::preventStrayRequests();
});

test('it logs in using remote pos kantin credentials and provisions offline access locally', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            return match ($request['action'] ?? null) {
                'login' => Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'session-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => [
                            'id' => 'USR-001',
                            'fullName' => 'Evander Smid Gidiin',
                            'email' => 'evandersmidgidiin@gmail.com',
                            'role' => 'admin',
                            'status' => 'aktif',
                            'authUpdatedAt' => '2026-04-28T10:00:00.000Z',
                        ],
                    ],
                ]),
                'createTrustedDevice' => Http::response([
                    'success' => true,
                    'message' => 'Trusted device dibuat.',
                    'data' => [
                        'token' => 'trusted-device-token',
                        'expiresAt' => now()->addDays(30)->toIso8601String(),
                        'user' => [
                            'id' => 'USR-001',
                        ],
                    ],
                ]),
                'syncPull' => Http::response([
                    'success' => true,
                    'message' => 'Sync pull berhasil.',
                    'data' => [
                        'users' => [[
                            'id' => 'USR-001',
                            'fullName' => 'Evander Smid Gidiin',
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
                        'suppliers' => [],
                        'transactions' => [],
                        'dailyFinance' => [],
                        'changeEntries' => [],
                        'supplierPayouts' => [],
                        'cursors' => [
                            'users' => '2026-04-28T10:00:00.000Z',
                            'buyers' => '',
                            'savings' => '',
                            'suppliers' => '',
                            'transactions' => '',
                            'dailyFinance' => '',
                            'changeEntries' => '',
                            'supplierPayouts' => '',
                        ],
                    ],
                ]),
                default => Http::response([
                    'success' => true,
                    'message' => 'OK',
                    'data' => ['results' => []],
                ]),
            };
        },
    ]);

    $response = $this->post('/login', [
        'email' => 'evandersmidgidiin@gmail.com',
        'password' => '12345678',
    ]);

    $user = User::query()->where('email', 'evandersmidgidiin@gmail.com')->firstOrFail();
    $credential = PosKantinDeviceCredential::query()->whereBelongsTo($user, 'user')->first();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/home');

    expect($user->remote_user_id)->toBe('USR-001')
        ->and($user->canUseOfflineLogin())->toBeTrue()
        ->and($credential)->not->toBeNull()
        ->and($credential?->trusted_device_token)->toBe('trusted-device-token');
});

test('it can log in offline when local trust is still valid', function () {
    Http::fake([
        'https://example.test/*' => Http::failedConnection(),
    ]);

    $user = User::factory()->create([
        'email' => 'offline@example.com',
        'password' => '12345678',
        'remote_user_id' => 'USR-OFFLINE',
        'role' => 'petugas',
        'status' => 'aktif',
        'offline_login_expires_at' => now()->addDays(5),
    ]);

    PosKantinDeviceCredential::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'remote_user_id' => 'USR-OFFLINE',
        'email' => 'offline@example.com',
        'trusted_device_token' => 'trusted-token',
        'trusted_device_expires_at' => now()->addDays(5),
    ]);

    $response = $this->post('/login', [
        'email' => 'offline@example.com',
        'password' => '12345678',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/home');
});

test('it rejects offline login when the local trust already expired', function () {
    Http::fake([
        'https://example.test/*' => Http::failedConnection(),
    ]);

    $user = User::factory()->create([
        'email' => 'expired@example.com',
        'password' => '12345678',
        'remote_user_id' => 'USR-EXPIRED',
        'role' => 'petugas',
        'status' => 'aktif',
        'offline_login_expires_at' => now()->subMinute(),
    ]);

    PosKantinDeviceCredential::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'remote_user_id' => 'USR-EXPIRED',
        'email' => 'expired@example.com',
        'trusted_device_token' => 'trusted-token',
        'trusted_device_expires_at' => now()->addDays(5),
    ]);

    $this->post('/login', [
        'email' => 'expired@example.com',
        'password' => '12345678',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('it still redirects home when post-login sync fails', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            return match ($request['action'] ?? null) {
                'login' => Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'session-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => [
                            'id' => 'USR-001',
                            'fullName' => 'Evander Smid Gidiin',
                            'email' => 'evandersmidgidiin@gmail.com',
                            'role' => 'admin',
                            'status' => 'aktif',
                            'authUpdatedAt' => '2026-04-28T10:00:00.000Z',
                        ],
                    ],
                ]),
                'createTrustedDevice' => Http::response([
                    'success' => true,
                    'message' => 'Trusted device dibuat.',
                    'data' => [
                        'token' => 'trusted-device-token',
                        'expiresAt' => now()->addDays(30)->toIso8601String(),
                        'user' => [
                            'id' => 'USR-001',
                        ],
                    ],
                ]),
                default => Http::response([
                    'success' => true,
                    'message' => 'OK',
                    'data' => ['results' => []],
                ]),
            };
        },
    ]);

    $this->mock(PosKantinSyncService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('sync')
            ->andThrow(new PosKantinException('Sinkronisasi login gagal.', [
                'category' => 'sync',
            ]));
    });

    $response = $this->post('/login', [
        'email' => 'evandersmidgidiin@gmail.com',
        'password' => '12345678',
    ]);

    $user = User::query()->where('email', 'evandersmidgidiin@gmail.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/home');
});
