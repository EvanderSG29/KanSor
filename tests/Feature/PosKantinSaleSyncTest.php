<?php

use App\Models\Food;
use App\Models\Sale;
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
        'queue.default' => 'sync',
    ]);

    Http::preventStrayRequests();
});

function saleSyncAdmin(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

function saleSyncPetugas(): User
{
    return User::factory()->create([
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

test('creating sale sends save transaction payload for each sale item with food reference', function () {
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
                'saveTransaction' => Http::response([
                    'success' => true,
                    'message' => 'Transaksi berhasil disimpan.',
                    'data' => [
                        'id' => $request['payload']->id ?? 'TRX-NEW',
                    ],
                ]),
            };
        },
    ]);

    $petugas = saleSyncPetugas();
    $assistant = saleSyncPetugas();
    $supplier = Supplier::factory()->create([
        'percentage_cut' => 10,
        'active' => true,
    ]);
    $food = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'name' => 'Bakwan',
        'active' => true,
    ]);

    $this->actingAs($petugas)
        ->post(route('kansor.sales.store'), [
            'date' => '2026-04-29',
            'supplier_id' => $supplier->id,
            'additional_users' => [$assistant->id],
            'items' => [[
                'food_id' => $food->id,
                'unit' => 'pcs',
                'quantity' => 10,
                'leftover' => 2,
                'price_per_unit' => 5000,
            ]],
        ])
        ->assertRedirect()
        ->assertSessionHas('sync_notice_status', 'queued');

    $sale = Sale::query()->with('items')->firstOrFail();
    $saleItem = $sale->items->firstOrFail();

    Http::assertSent(function (Request $request) use ($petugas, $supplier, $food, $sale, $saleItem): bool {
        return ($request['action'] ?? null) === 'saveTransaction'
            && ($request['payload']->id ?? null) === 'SALEITEM-'.$saleItem->getKey()
            && ($request['payload']->transactionDate ?? null) === '2026-04-29'
            && (string) ($request['payload']->inputByUserId ?? '') === (string) $petugas->getKey()
            && ($request['payload']->inputByName ?? null) === $petugas->name
            && (string) ($request['payload']->supplierId ?? '') === (string) $supplier->getKey()
            && (string) ($request['payload']->clientSaleId ?? '') === (string) $sale->getKey()
            && (string) ($request['payload']->clientSaleItemId ?? '') === (string) $saleItem->getKey()
            && (string) ($request['payload']->foodId ?? '') === (string) $food->getKey()
            && ($request['payload']->itemName ?? null) === 'Bakwan'
            && (int) ($request['payload']->quantity ?? -1) === 10
            && (int) ($request['payload']->remainingQuantity ?? -1) === 2
            && (int) ($request['payload']->grossSales ?? -1) === 50000
            && (int) ($request['payload']->commissionAmount ?? -1) === 5000
            && (int) ($request['payload']->supplierNetAmount ?? -1) === 45000
            && str_contains((string) ($request['payload']->notes ?? ''), 'saleId='.$sale->getKey());
    });
});

test('updating sale deletes removed transaction rows and saves current sale items', function () {
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
                'saveTransaction' => Http::response([
                    'success' => true,
                    'message' => 'Transaksi berhasil disimpan.',
                    'data' => [
                        'id' => $request['payload']->id ?? 'TRX-UPD',
                    ],
                ]),
                'deleteTransaction' => Http::response([
                    'success' => true,
                    'message' => 'Transaksi berhasil dihapus.',
                    'data' => [
                        'id' => $request['payload']->id ?? 'TRX-DEL',
                    ],
                ]),
            };
        },
    ]);

    $admin = saleSyncAdmin();
    $supplier = Supplier::factory()->create([
        'percentage_cut' => 10,
        'active' => true,
    ]);
    $foodA = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'name' => 'Bakwan',
        'active' => true,
    ]);
    $foodB = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'name' => 'Risol',
        'active' => true,
    ]);

    $sale = Sale::factory()->create([
        'date' => '2026-04-28',
        'supplier_id' => $supplier->id,
        'user_id' => $admin->id,
    ]);
    $keptItem = $sale->items()->create([
        'food_id' => $foodA->id,
        'unit' => 'pcs',
        'quantity' => 10,
        'leftover' => 1,
        'price_per_unit' => 5000,
        'total_item' => 50000,
        'cut_amount' => 5000,
    ]);
    $removedItem = $sale->items()->create([
        'food_id' => $foodB->id,
        'unit' => 'pcs',
        'quantity' => 5,
        'leftover' => 0,
        'price_per_unit' => 4000,
        'total_item' => 20000,
        'cut_amount' => 2000,
    ]);

    $this->actingAs($admin)
        ->put(route('kansor.admin.sales.update', $sale), [
            'date' => '2026-04-29',
            'supplier_id' => $supplier->id,
            'items' => [[
                'id' => $keptItem->getKey(),
                'food_id' => $foodA->getKey(),
                'unit' => 'bungkus',
                'quantity' => 8,
                'leftover' => 0,
                'price_per_unit' => 6000,
            ]],
        ])
        ->assertRedirect(route('kansor.admin.sales.show', $sale))
        ->assertSessionHas('sync_notice_status', 'queued');

    Http::assertSent(function (Request $request) use ($removedItem): bool {
        return ($request['action'] ?? null) === 'deleteTransaction'
            && ($request['payload']->id ?? null) === 'SALEITEM-'.$removedItem->getKey();
    });

    Http::assertSent(function (Request $request) use ($keptItem, $foodA): bool {
        return ($request['action'] ?? null) === 'saveTransaction'
            && ($request['payload']->id ?? null) === 'SALEITEM-'.$keptItem->getKey()
            && (string) ($request['payload']->foodId ?? '') === (string) $foodA->getKey()
            && (string) ($request['payload']->clientSaleId ?? '') === (string) $sale->getKey()
            && (string) ($request['payload']->clientSaleItemId ?? '') === (string) $keptItem->getKey()
            && ($request['payload']->unitName ?? null) === 'bungkus'
            && (int) ($request['payload']->grossSales ?? -1) === 48000;
    });
});

test('deleting sale sends delete transaction for every remote sale item row', function () {
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
                'deleteTransaction' => Http::response([
                    'success' => true,
                    'message' => 'Transaksi berhasil dihapus.',
                    'data' => [
                        'id' => $request['payload']->id ?? 'TRX-DEL',
                    ],
                ]),
            };
        },
    ]);

    $petugas = saleSyncPetugas();
    $supplier = Supplier::factory()->create(['active' => true]);
    $food = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'active' => true,
    ]);
    $sale = Sale::factory()->create([
        'date' => now('Asia/Jakarta')->toDateString(),
        'supplier_id' => $supplier->id,
        'user_id' => $petugas->id,
    ]);
    $item = $sale->items()->create([
        'food_id' => $food->id,
        'unit' => 'pcs',
        'quantity' => 3,
        'leftover' => 1,
        'price_per_unit' => 4000,
        'total_item' => 12000,
        'cut_amount' => 1200,
    ]);

    $this->actingAs($petugas)
        ->delete(route('kansor.sales.destroy', $sale))
        ->assertRedirect(route('kansor.sales.index'))
        ->assertSessionHas('sync_notice_status', 'queued');

    Http::assertSent(function (Request $request) use ($item): bool {
        return ($request['action'] ?? null) === 'deleteTransaction'
            && ($request['payload']->id ?? null) === 'SALEITEM-'.$item->getKey();
    });
});

