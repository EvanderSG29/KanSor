<?php

use App\Models\Food;
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

function foodSyncAdmin(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

test('creating food sends apps script compatible save food payload', function () {
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
                'saveFood' => Http::response([
                    'success' => true,
                    'message' => 'Makanan berhasil disimpan.',
                    'data' => [
                        'id' => 'FOD-NEW',
                    ],
                ]),
            };
        },
    ]);

    $admin = foodSyncAdmin();
    $supplier = Supplier::factory()->create(['active' => true]);

    $this->actingAs($admin)
        ->post(route('kansor.admin.foods.store'), [
            'supplier_id' => $supplier->id,
            'name' => 'Bakwan',
            'unit' => 'pcs',
            'default_price' => 2500,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.foods.index'))
        ->assertSessionMissing('warning');

    Http::assertSent(function (Request $request) use ($supplier): bool {
        return ($request['action'] ?? null) === 'saveFood'
            && (string) ($request['payload']->supplierId ?? '') === (string) $supplier->getKey()
            && ($request['payload']->name ?? null) === 'Bakwan'
            && ($request['payload']->unit ?? null) === 'pcs'
            && (int) ($request['payload']->defaultPrice ?? -1) === 2500
            && ($request['payload']->isActive ?? null) === true;
    });
});

test('updating food sends mapped save food payload', function () {
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
                'saveFood' => Http::response([
                    'success' => true,
                    'message' => 'Makanan berhasil disimpan.',
                    'data' => [
                        'id' => 'FOD-UPD',
                    ],
                ]),
            };
        },
    ]);

    $admin = foodSyncAdmin();
    $oldSupplier = Supplier::factory()->create(['active' => true]);
    $newSupplier = Supplier::factory()->create(['active' => true]);
    $food = Food::factory()->create([
        'supplier_id' => $oldSupplier->id,
        'name' => 'Bakwan Lama',
        'unit' => 'pcs',
        'default_price' => 2000,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('kansor.admin.foods.update', $food), [
            'supplier_id' => $newSupplier->id,
            'name' => 'Bakwan Baru',
            'unit' => 'bungkus',
            'default_price' => 3000,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.foods.index'))
        ->assertSessionMissing('warning');

    Http::assertSent(function (Request $request) use ($food, $newSupplier): bool {
        return ($request['action'] ?? null) === 'saveFood'
            && (string) ($request['payload']->id ?? '') === (string) $food->getKey()
            && (string) ($request['payload']->supplierId ?? '') === (string) $newSupplier->getKey()
            && ($request['payload']->name ?? null) === 'Bakwan Baru'
            && ($request['payload']->unit ?? null) === 'bungkus'
            && (int) ($request['payload']->defaultPrice ?? -1) === 3000
            && ($request['payload']->isActive ?? null) === true;
    });
});

test('deactivating food still sends full save food payload', function () {
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
                'saveFood' => Http::response([
                    'success' => true,
                    'message' => 'Makanan berhasil disimpan.',
                    'data' => [
                        'id' => 'FOD-DEL',
                    ],
                ]),
            };
        },
    ]);

    $admin = foodSyncAdmin();
    $supplier = Supplier::factory()->create(['active' => true]);
    $food = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'name' => 'Risol Mayo',
        'unit' => 'pcs',
        'default_price' => 4000,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('kansor.admin.foods.destroy', $food))
        ->assertRedirect(route('kansor.admin.foods.index'))
        ->assertSessionMissing('warning');

    Http::assertSent(function (Request $request) use ($food, $supplier): bool {
        return ($request['action'] ?? null) === 'saveFood'
            && (string) ($request['payload']->id ?? '') === (string) $food->getKey()
            && (string) ($request['payload']->supplierId ?? '') === (string) $supplier->getKey()
            && ($request['payload']->name ?? null) === 'Risol Mayo'
            && ($request['payload']->unit ?? null) === 'pcs'
            && (int) ($request['payload']->defaultPrice ?? -1) === 4000
            && ($request['payload']->isActive ?? null) === false;
    });
});

