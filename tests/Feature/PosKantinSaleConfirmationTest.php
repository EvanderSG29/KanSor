<?php

use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    $this->petugas = User::factory()->create([
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    $this->supplier = Supplier::factory()->create([
        'active' => true,
    ]);
});

test('supplier confirmation does not overwrite canteen deposit fields', function () {
    $existingCanteenConfirmer = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
        'name' => 'Admin Setoran Lama',
    ]);

    $sale = Sale::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->petugas->id,
        'status_i' => Sale::STATUS_PENDING,
        'status_ii' => Sale::STATUS_CANTEEN_DEPOSITED,
        'canteen_deposited_at' => '2026-04-28',
        'canteen_deposited_amount' => 4000,
        'canteen_deposit_note' => 'Setor kas lama',
        'canteen_deposit_confirmed_by' => $existingCanteenConfirmer->id,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('kansor.admin.sales.confirm-supplier-paid', $sale), [
            'paid_at' => '2026-04-30',
            'paid_amount' => 18000,
            'taken_note' => 'Pembayaran pemasok lunas',
        ])
        ->assertRedirect();

    $sale->refresh();

    expect($sale->status_i)->toBe(Sale::STATUS_SUPPLIER_PAID)
        ->and($sale->supplier_paid_at?->format('Y-m-d'))->toBe('2026-04-30')
        ->and($sale->supplier_paid_amount)->toBe(18000)
        ->and($sale->supplier_payment_note)->toBe('Pembayaran pemasok lunas')
        ->and($sale->supplier_payment_confirmed_by)->toBe($this->admin->id)
        ->and($sale->status_ii)->toBe(Sale::STATUS_CANTEEN_DEPOSITED)
        ->and($sale->canteen_deposited_at?->format('Y-m-d'))->toBe('2026-04-28')
        ->and($sale->canteen_deposited_amount)->toBe(4000)
        ->and($sale->canteen_deposit_note)->toBe('Setor kas lama')
        ->and($sale->canteen_deposit_confirmed_by)->toBe($existingCanteenConfirmer->id);
});

test('canteen deposit confirmation does not overwrite supplier payment fields', function () {
    $existingSupplierConfirmer = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
        'name' => 'Admin Supplier Lama',
    ]);

    $sale = Sale::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->petugas->id,
        'status_i' => Sale::STATUS_SUPPLIER_PAID,
        'status_ii' => Sale::STATUS_PENDING,
        'supplier_paid_at' => '2026-04-27',
        'supplier_paid_amount' => 16000,
        'supplier_payment_note' => 'Bayar tahap pertama',
        'supplier_payment_confirmed_by' => $existingSupplierConfirmer->id,
        'total_canteen' => 3500,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('kansor.admin.sales.confirm-canteen-deposited', $sale), [
            'paid_at' => '2026-04-30',
            'paid_amount' => 3500,
            'taken_note' => 'Setor ke kas harian',
        ])
        ->assertRedirect();

    $sale->refresh();

    expect($sale->status_ii)->toBe(Sale::STATUS_CANTEEN_DEPOSITED)
        ->and($sale->canteen_deposited_at?->format('Y-m-d'))->toBe('2026-04-30')
        ->and($sale->canteen_deposited_amount)->toBe(3500)
        ->and($sale->canteen_deposit_note)->toBe('Setor ke kas harian')
        ->and($sale->canteen_deposit_confirmed_by)->toBe($this->admin->id)
        ->and($sale->status_i)->toBe(Sale::STATUS_SUPPLIER_PAID)
        ->and($sale->supplier_paid_at?->format('Y-m-d'))->toBe('2026-04-27')
        ->and($sale->supplier_paid_amount)->toBe(16000)
        ->and($sale->supplier_payment_note)->toBe('Bayar tahap pertama')
        ->and($sale->supplier_payment_confirmed_by)->toBe($existingSupplierConfirmer->id);
});

test('admin sale detail shows separate supplier and canteen confirmation blocks', function () {
    $supplierConfirmer = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
        'name' => 'Admin Supplier',
    ]);

    $canteenConfirmer = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
        'name' => 'Admin Setoran',
    ]);

    $sale = Sale::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->petugas->id,
        'status_i' => Sale::STATUS_SUPPLIER_PAID,
        'status_ii' => Sale::STATUS_CANTEEN_DEPOSITED,
        'supplier_paid_at' => '2026-04-30',
        'supplier_paid_amount' => 18000,
        'supplier_payment_note' => 'Bayar supplier lunas',
        'supplier_payment_confirmed_by' => $supplierConfirmer->id,
        'canteen_deposited_at' => '2026-04-30',
        'canteen_deposited_amount' => 4000,
        'canteen_deposit_note' => 'Setor ke kas',
        'canteen_deposit_confirmed_by' => $canteenConfirmer->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('kansor.admin.sales.show', $sale))
        ->assertSuccessful()
        ->assertSee('Konfirmasi pemasok')
        ->assertSee('Konfirmasi setoran kantin')
        ->assertSee('Bayar supplier lunas')
        ->assertSee('Setor ke kas')
        ->assertSee('Admin Supplier')
        ->assertSee('Admin Setoran')
        ->assertSee('Rp 18.000')
        ->assertSee('Rp 4.000');
});

