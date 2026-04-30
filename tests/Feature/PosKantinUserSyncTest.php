<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.pos_kantin.api_url' => 'https://example.test/macros/s/api/exec',
        'services.pos_kantin.admin_email' => 'evandersmidgidiin@gmail.com',
        'services.pos_kantin.admin_password' => 'secret-password',
        'services.pos_kantin.timeout' => 20,
        'services.pos_kantin.connect_timeout' => 10,
        'queue.default' => 'sync',
    ]);

    Http::preventStrayRequests();
});

function userSyncAdmin(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

test('creating user sends apps script compatible save user payload', function () {
    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('', 200),
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
                'saveUser' => Http::response([
                    'success' => true,
                    'message' => 'User berhasil disimpan.',
                    'data' => [
                        'id' => 'USR-NEW',
                    ],
                ]),
            };
        },
    ]);

    $admin = userSyncAdmin();

    $this->actingAs($admin)
        ->post(route('pos-kantin.admin.users.store'), [
            'name' => 'Petugas Baru',
            'email' => 'petugas-baru@example.com',
            'password' => 'KanSor!Pass123',
            'password_confirmation' => 'KanSor!Pass123',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('pos-kantin.admin.users.index'))
        ->assertSessionHas('sync_notice_status', 'queued');

    Http::assertSent(function (Request $request): bool {
        return ($request['action'] ?? null) === 'saveUser'
            && ($request['payload']->fullName ?? null) === 'Petugas Baru'
            && ($request['payload']->nickname ?? null) === 'Petugas'
            && ($request['payload']->email ?? null) === 'petugas-baru@example.com'
            && ($request['payload']->role ?? null) === User::ROLE_PETUGAS
            && ($request['payload']->status ?? null) === User::STATUS_ACTIVE
            && ($request['payload']->classGroup ?? null) === ''
            && ($request['payload']->notes ?? null) === ''
            && ($request['payload']->password ?? null) === 'KanSor!Pass123';
    });
});

test('updating user sends mapped save user payload and omits password when not changed', function () {
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
                'saveUser' => Http::response([
                    'success' => true,
                    'message' => 'User berhasil disimpan.',
                    'data' => [
                        'id' => 'USR-UPD',
                    ],
                ]),
            };
        },
    ]);

    $admin = userSyncAdmin();
    $user = User::factory()->create([
        'name' => 'Petugas Lama',
        'email' => 'petugas-lama@example.com',
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('pos-kantin.admin.users.update', $user), [
            'name' => 'Petugas Baru',
            'email' => 'petugas-baru@example.com',
            'role' => User::ROLE_ADMIN,
            'active' => '1',
        ])
        ->assertRedirect(route('pos-kantin.admin.users.index'))
        ->assertSessionHas('sync_notice_status', 'queued');

    Http::assertSent(function (Request $request) use ($user): bool {
        return ($request['action'] ?? null) === 'saveUser'
            && (string) ($request['payload']->id ?? '') === (string) $user->getKey()
            && ($request['payload']->fullName ?? null) === 'Petugas Baru'
            && ($request['payload']->nickname ?? null) === 'Petugas'
            && ($request['payload']->email ?? null) === 'petugas-baru@example.com'
            && ($request['payload']->role ?? null) === User::ROLE_ADMIN
            && ($request['payload']->status ?? null) === User::STATUS_ACTIVE
            && ! isset($request['payload']->password);
    });
});

test('deactivating user still sends full save user payload', function () {
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
                'saveUser' => Http::response([
                    'success' => true,
                    'message' => 'User berhasil disimpan.',
                    'data' => [
                        'id' => 'USR-DEL',
                    ],
                ]),
            };
        },
    ]);

    $admin = userSyncAdmin();
    User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
    $user = User::factory()->create([
        'name' => 'Petugas Nonaktif',
        'email' => 'petugas-nonaktif@example.com',
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('pos-kantin.admin.users.destroy', $user))
        ->assertRedirect(route('pos-kantin.admin.users.index'))
        ->assertSessionHas('sync_notice_status', 'queued');

    Http::assertSent(function (Request $request) use ($user): bool {
        return ($request['action'] ?? null) === 'saveUser'
            && (string) ($request['payload']->id ?? '') === (string) $user->getKey()
            && ($request['payload']->fullName ?? null) === 'Petugas Nonaktif'
            && ($request['payload']->nickname ?? null) === 'Petugas'
            && ($request['payload']->email ?? null) === 'petugas-nonaktif@example.com'
            && ($request['payload']->status ?? null) === User::STATUS_INACTIVE;
    });
});
