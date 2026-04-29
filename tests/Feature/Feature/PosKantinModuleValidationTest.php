<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transaction module validates invalid filters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pos-kantin.transactions.index', [
            'startDate' => '2026-04-27',
            'endDate' => '2026-04-01',
            'pageSize' => 1000,
            'commissionBaseType' => 'invalid-type',
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'endDate',
            'pageSize',
            'commissionBaseType',
        ]);
});

test('supplier module validates invalid filter values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pos-kantin.suppliers.index', [
            'includeInactive' => 'kadang',
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'includeInactive',
        ]);
});

test('module pages render empty local data gracefully', function (string $routeName, string $expectedText, array $parameters = []) {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-EMPTY',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->get(route($routeName, $parameters))
        ->assertSuccessful()
        ->assertSee($expectedText);
})->with([
    'transactions' => ['pos-kantin.transactions.index', 'Belum ada data transaksi.', []],
    'savings' => ['pos-kantin.savings.index', 'Belum ada data simpanan.', []],
    'suppliers' => ['pos-kantin.suppliers.index', 'Belum ada data pemasok.', []],
    'supplier payouts' => ['pos-kantin.supplier-payouts.index', 'Belum ada payout outstanding.', []],
    'users' => ['pos-kantin.users.index', 'Belum ada data pengguna.', []],
]);
