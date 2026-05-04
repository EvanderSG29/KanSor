<?php

use App\Models\CanteenTotal;
use App\Models\Food;
use App\Models\Preference;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.kansor.api_url' => null,
    ]);

    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('', 200),
    ]);
});

function adminUser(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

function petugasUser(): User
{
    return User::factory()->create([
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

test('admin can create local user and password is hashed', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('kansor.admin.users.store'), [
            'name' => 'Petugas Baru',
            'email' => 'petugas-baru@example.com',
            'password' => 'KanSor!Pass123',
            'password_confirmation' => 'KanSor!Pass123',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.index'));

    $user = User::query()->where('email', 'petugas-baru@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user?->role)->toBe(User::ROLE_PETUGAS)
        ->and($user?->active)->toBeTrue()
        ->and(Hash::check('KanSor!Pass123', (string) $user?->password))->toBeTrue();
});

test('user email must stay unique on admin create', function () {
    $admin = adminUser();
    User::factory()->create([
        'email' => 'sama@example.com',
        'role' => User::ROLE_PETUGAS,
        'active' => true,
        'status' => User::STATUS_ACTIVE,
    ]);

    $this->actingAs($admin)
        ->from(route('kansor.admin.users.create'))
        ->post(route('kansor.admin.users.store'), [
            'name' => 'Duplikat',
            'email' => 'sama@example.com',
            'password' => 'KanSor!Pass123',
            'password_confirmation' => 'KanSor!Pass123',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.create'))
        ->assertSessionHasErrors('email');
});

test('petugas cannot access local user crud admin routes', function () {
    $petugas = petugasUser();

    $this->actingAs($petugas)
        ->get(route('kansor.admin.users.index'))
        ->assertForbidden();
});

test('admin cannot deactivate the last active admin', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->delete(route('kansor.admin.users.destroy', $admin))
        ->assertSessionHas('error');

    expect($admin->fresh()?->active)->toBeTrue();
});

test('admin can create supplier and inactive supplier is hidden from sale create page', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('kansor.admin.suppliers.store'), [
            'name' => 'Supplier Aktif',
            'contact_info' => '08123',
            'percentage_cut' => 10,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.suppliers.index'));

    $inactiveSupplier = Supplier::factory()->create([
        'name' => 'Supplier Nonaktif',
        'active' => false,
    ]);

    $petugas = petugasUser();

    $this->actingAs($petugas)
        ->get(route('kansor.sales.create'))
        ->assertSuccessful()
        ->assertSee('Supplier Aktif')
        ->assertDontSee($inactiveSupplier->name);
});

test('admin can create food and inactive food is hidden from sale create page', function () {
    $admin = adminUser();
    $supplier = Supplier::factory()->create(['active' => true]);
    $inactiveSupplier = Supplier::factory()->create(['active' => false]);

    $this->actingAs($admin)
        ->post(route('kansor.admin.foods.store'), [
            'supplier_id' => $supplier->id,
            'name' => 'Bakwan',
            'unit' => 'pcs',
            'default_price' => 2000,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.foods.index'));

    $inactiveFood = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'name' => 'Gorengan Lama',
        'active' => false,
    ]);

    $foodFromInactiveSupplier = Food::factory()->create([
        'supplier_id' => $inactiveSupplier->id,
        'name' => 'Makanan Supplier Mati',
        'active' => true,
    ]);

    $petugas = petugasUser();

    $this->actingAs($petugas)
        ->get(route('kansor.sales.create'))
        ->assertSuccessful()
        ->assertSee('Bakwan')
        ->assertDontSee($inactiveFood->name)
        ->assertDontSee($foodFromInactiveSupplier->name);
});

test('sale create page shows live summary section and quick item controls', function () {
    $petugas = petugasUser();
    $supplier = Supplier::factory()->create(['active' => true]);

    Food::factory()->create([
        'supplier_id' => $supplier->id,
        'name' => 'Nasi Uduk',
        'unit' => 'porsi',
        'default_price' => 12000,
        'active' => true,
    ]);

    $this->actingAs($petugas)
        ->get(route('kansor.sales.create'))
        ->assertSuccessful()
        ->assertSee('Ringkasan sebelum simpan')
        ->assertSee('Total Terjual')
        ->assertSee('Total Pemasok')
        ->assertSee('Total Kantin')
        ->assertSee('Jumlah Item')
        ->assertSee('Tambah Item')
        ->assertSee('inputmode="numeric"', false)
        ->assertSee('data-sale-summary="gross"', false);
});

test('petugas can create sale and totals are calculated correctly', function () {
    $petugas = petugasUser();
    $assistant = petugasUser();
    $supplier = Supplier::factory()->create([
        'percentage_cut' => 10,
        'active' => true,
    ]);
    $food = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'active' => true,
    ]);

    $this->actingAs($petugas)
        ->post(route('kansor.sales.store'), [
            'date' => now('Asia/Jakarta')->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'additional_users' => [$assistant->id],
            'items' => [
                [
                    'food_id' => $food->id,
                    'unit' => 'pcs',
                    'quantity' => 10,
                    'leftover' => 2,
                    'price_per_unit' => 5000,
                ],
            ],
        ])
        ->assertRedirect();

    $sale = Sale::query()->with('items')->first();

    expect($sale)->not->toBeNull()
        ->and($sale?->total_canteen)->toBe(5000)
        ->and($sale?->total_supplier)->toBe(45000)
        ->and($sale?->items)->toHaveCount(1)
        ->and($sale?->items->first()?->total_item)->toBe(50000)
        ->and($sale?->items->first()?->cut_amount)->toBe(5000);
});

test('active sale must be unique for the same supplier and date', function () {
    $petugas = petugasUser();
    $supplier = Supplier::factory()->create(['active' => true]);
    $food = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'active' => true,
    ]);

    Sale::factory()->create([
        'date' => now('Asia/Jakarta')->format('Y-m-d'),
        'supplier_id' => $supplier->id,
        'user_id' => $petugas->id,
    ]);

    $this->actingAs($petugas)
        ->from(route('kansor.sales.create'))
        ->post(route('kansor.sales.store'), [
            'date' => now('Asia/Jakarta')->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'food_id' => $food->id,
                    'unit' => 'pcs',
                    'quantity' => 1,
                    'leftover' => 0,
                    'price_per_unit' => 1000,
                ],
            ],
        ])
        ->assertRedirect(route('kansor.sales.create'))
        ->assertSessionHasErrors('date');
});

test('sale validation rejects leftover greater than quantity', function () {
    $petugas = petugasUser();
    $supplier = Supplier::factory()->create(['active' => true]);
    $food = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'active' => true,
    ]);

    $this->actingAs($petugas)
        ->from(route('kansor.sales.create'))
        ->post(route('kansor.sales.store'), [
            'date' => now('Asia/Jakarta')->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'food_id' => $food->id,
                    'unit' => 'pcs',
                    'quantity' => 2,
                    'leftover' => 3,
                    'price_per_unit' => 1000,
                ],
            ],
        ])
        ->assertRedirect(route('kansor.sales.create'))
        ->assertSessionHasErrors('items.0.leftover');
});

test('sale becomes read only for petugas after admin confirmation', function () {
    $admin = adminUser();
    $petugas = petugasUser();
    $supplier = Supplier::factory()->create(['active' => true]);
    $food = Food::factory()->create(['supplier_id' => $supplier->id, 'active' => true]);
    $sale = Sale::factory()->create([
        'supplier_id' => $supplier->id,
        'user_id' => $petugas->id,
        'date' => now('Asia/Jakarta')->format('Y-m-d'),
    ]);
    $sale->items()->create([
        'food_id' => $food->id,
        'unit' => 'pcs',
        'quantity' => 2,
        'leftover' => 0,
        'price_per_unit' => 5000,
        'total_item' => 10000,
        'cut_amount' => 1000,
    ]);
    $sale->update([
        'total_supplier' => 9000,
        'total_canteen' => 1000,
    ]);

    $this->actingAs($admin)
        ->patch(route('kansor.admin.sales.confirm-supplier-paid', $sale), [
            'paid_at' => now('Asia/Jakarta')->format('Y-m-d'),
            'paid_amount' => 9000,
        ])
        ->assertSessionHas('status');

    $this->actingAs($petugas)
        ->get(route('kansor.sales.edit', $sale))
        ->assertForbidden();
});

test('admin can confirm canteen deposited and canteen total is recalculated', function () {
    $admin = adminUser();
    $petugas = petugasUser();
    $supplier = Supplier::factory()->create(['active' => true, 'percentage_cut' => 20]);
    $food = Food::factory()->create(['supplier_id' => $supplier->id, 'active' => true]);

    $sale = Sale::factory()->create([
        'supplier_id' => $supplier->id,
        'user_id' => $petugas->id,
        'date' => now('Asia/Jakarta')->format('Y-m-d'),
        'total_supplier' => 8000,
        'total_canteen' => 2000,
    ]);
    $sale->items()->create([
        'food_id' => $food->id,
        'unit' => 'pcs',
        'quantity' => 1,
        'leftover' => 0,
        'price_per_unit' => 10000,
        'total_item' => 10000,
        'cut_amount' => 2000,
    ]);

    $this->actingAs($admin)
        ->patch(route('kansor.admin.sales.confirm-canteen-deposited', $sale), [
            'paid_at' => now('Asia/Jakarta')->format('Y-m-d'),
            'paid_amount' => 2000,
            'taken_note' => 'Setor kas',
        ])
        ->assertSessionHas('status');

    $sale->refresh();
    $canteenTotal = CanteenTotal::query()->whereDate('date', $sale->date)->first();

    expect($sale->status_ii)->toBe(Sale::STATUS_CANTEEN_DEPOSITED)
        ->and($sale->canteen_deposited_amount)->toBe(2000)
        ->and($sale->canteen_deposit_note)->toBe('Setor kas')
        ->and($canteenTotal?->total_amount)->toBe(2000);
});

test('user can save whitelisted preferences', function () {
    $petugas = petugasUser();

    $this->actingAs($petugas)
        ->post(route('kansor.preferences.store'), [
            'sync_interval' => 120,
            'theme' => 'dark',
            'rows_per_page' => 25,
        ])
        ->assertRedirect(route('kansor.preferences.index'));

    expect(Preference::query()->where('user_id', $petugas->id)->count())->toBe(3)
        ->and(Preference::query()->where('user_id', $petugas->id)->where('key', 'theme')->first()?->value)->toBe('dark');
});

