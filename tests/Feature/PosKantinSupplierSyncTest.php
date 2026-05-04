<?php

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.kansor.api_url' => 'https://example.test/macros/s/api/exec',
        'services.kansor.admin_email' => 'evandersmidgidiin@gmail.com',
        'services.kansor.admin_password' => 'secret-password',
        'services.kansor.timeout' => 20,
        'services.kansor.connect_timeout' => 10,
    ]);

    Http::preventStrayRequests();
});

function supplierSyncAdmin(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

test('creating supplier sends apps script compatible save supplier payload', function () {
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

    $admin = supplierSyncAdmin();

    $this->actingAs($admin)
        ->post(route('kansor.admin.suppliers.store'), [
            'name' => 'Supplier Aktif',
            'contact_info' => '08123',
            'percentage_cut' => 10,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.suppliers.index'))
        ->assertSessionHas('sync_notice_status', 'queued');

    Http::assertSent(function (Request $request): bool {
        return ($request['action'] ?? null) === 'saveSupplier'
            && ($request['payload']->supplierName ?? null) === 'Supplier Aktif'
            && ($request['payload']->contactName ?? null) === ''
            && ($request['payload']->contactPhone ?? null) === '08123'
            && (float) ($request['payload']->commissionRate ?? -1) === 10.0
            && ($request['payload']->commissionBaseType ?? null) === 'revenue'
            && (int) ($request['payload']->payoutTermDays ?? -1) === 0
            && ($request['payload']->notes ?? null) === ''
            && ($request['payload']->isActive ?? null) === true;
    });
});

test('updating supplier sends mapped save supplier payload', function () {
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
                        'id' => 'SUP-UPD',
                    ],
                ]),
            };
        },
    ]);

    $admin = supplierSyncAdmin();
    $supplier = Supplier::factory()->create([
        'name' => 'Supplier Lama',
        'contact_info' => '08123',
        'percentage_cut' => 10,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('kansor.admin.suppliers.update', $supplier), [
            'name' => 'Supplier Baru',
            'contact_info' => 'Pak Latif - 08123 45678',
            'percentage_cut' => 12.5,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.suppliers.index'))
        ->assertSessionHas('sync_notice_status', 'queued');

    Http::assertSent(function (Request $request) use ($supplier): bool {
        return ($request['action'] ?? null) === 'saveSupplier'
            && (string) ($request['payload']->id ?? '') === (string) $supplier->getKey()
            && ($request['payload']->supplierName ?? null) === 'Supplier Baru'
            && ($request['payload']->contactName ?? null) === 'Pak Latif'
            && ($request['payload']->contactPhone ?? null) === '08123 45678'
            && (float) ($request['payload']->commissionRate ?? -1) === 12.5
            && ($request['payload']->isActive ?? null) === true;
    });
});

test('deactivating supplier still sends full save supplier payload', function () {
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
                        'id' => 'SUP-DEL',
                    ],
                ]),
            };
        },
    ]);

    $admin = supplierSyncAdmin();
    $supplier = Supplier::factory()->create([
        'name' => 'Supplier Nonaktif',
        'contact_info' => '08123',
        'percentage_cut' => 10,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('kansor.admin.suppliers.destroy', $supplier))
        ->assertRedirect(route('kansor.admin.suppliers.index'))
        ->assertSessionHas('sync_notice_status', 'queued');

    Http::assertSent(function (Request $request) use ($supplier): bool {
        return ($request['action'] ?? null) === 'saveSupplier'
            && (string) ($request['payload']->id ?? '') === (string) $supplier->getKey()
            && ($request['payload']->supplierName ?? null) === 'Supplier Nonaktif'
            && ($request['payload']->contactPhone ?? null) === '08123'
            && ($request['payload']->isActive ?? null) === false;
    });
});

