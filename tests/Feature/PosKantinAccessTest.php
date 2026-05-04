<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function activeAdminUser(): User
{
    return User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

function activePetugasUser(): User
{
    return User::factory()->create([
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

function inactivePosUser(): User
{
    return User::factory()->create([
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_INACTIVE,
        'active' => false,
    ]);
}

function invalidRoleUser(): User
{
    return User::factory()->create([
        'role' => 'viewer',
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
}

test('guest cannot access pos routes', function (string $routeName, array $parameters = []) {
    $this->get(route($routeName, $parameters))
        ->assertRedirect(route('login'));
})->with([
    'home dashboard' => ['home'],
    'sales index' => ['kansor.sales.index'],
    'sync index' => ['kansor.sync.index'],
    'admin users index' => ['kansor.admin.users.index'],
]);

test('inactive user cannot access pos routes', function (string $routeName, array $parameters = []) {
    $this->actingAs(inactivePosUser())
        ->get(route($routeName, $parameters))
        ->assertForbidden();
})->with([
    'home dashboard' => ['home'],
    'sales index' => ['kansor.sales.index'],
    'sync index' => ['kansor.sync.index'],
]);

test('user with invalid role cannot access pos routes', function (string $routeName, array $parameters = []) {
    $this->actingAs(invalidRoleUser())
        ->get(route($routeName, $parameters))
        ->assertForbidden();
})->with([
    'home dashboard' => ['home'],
    'sales index' => ['kansor.sales.index'],
    'sync index' => ['kansor.sync.index'],
]);

test('petugas cannot access admin routes', function (string $routeName, array $parameters = []) {
    $this->actingAs(activePetugasUser())
        ->get(route($routeName, $parameters))
        ->assertForbidden();
})->with([
    'admin users index' => ['kansor.admin.users.index'],
    'admin sales index' => ['kansor.admin.sales.index'],
    'admin audit logs index' => ['kansor.admin.audit-logs.index'],
]);

test('admin can access admin routes', function (string $routeName, array $parameters = []) {
    $this->actingAs(activeAdminUser())
        ->get(route($routeName, $parameters))
        ->assertSuccessful();
})->with([
    'admin users index' => ['kansor.admin.users.index'],
    'admin sales index' => ['kansor.admin.sales.index'],
    'admin audit logs index' => ['kansor.admin.audit-logs.index'],
]);

test('petugas can access pos sales routes', function (string $routeName, array $parameters = []) {
    $this->actingAs(activePetugasUser())
        ->get(route($routeName, $parameters))
        ->assertSuccessful();
})->with([
    'home dashboard' => ['home'],
    'sales index' => ['kansor.sales.index'],
    'preferences index' => ['kansor.preferences.index'],
]);

test('petugas sees only the operational sidebar menu', function () {
    $this->actingAs(activePetugasUser())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Input Transaksi')
        ->assertSee('Riwayat Transaksi')
        ->assertSee('Status Sinkronisasi')
        ->assertSee('Preferensi')
        ->assertDontSee('Kelola Pengguna')
        ->assertDontSee('Kelola Pemasok')
        ->assertDontSee('Data Server')
        ->assertDontSee('Konfirmasi Pembayaran & Setoran');
});

test('admin sees the role-based sidebar without technical labels', function () {
    $this->actingAs(activeAdminUser())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Semua Transaksi')
        ->assertSee('Konfirmasi Pembayaran & Setoran')
        ->assertSee('Kelola Pengguna')
        ->assertSee('Kelola Pemasok')
        ->assertSee('Kelola Menu / Makanan')
        ->assertSee('Audit Aktivitas')
        ->assertSee('Data Server')
        ->assertSee('Data Transaksi Server')
        ->assertSee('Laporan Operasional')
        ->assertDontSee('Transaksi Lokal')
        ->assertDontSee('Snapshot Transaksi')
        ->assertDontSee('Snapshot Pemasok')
        ->assertDontSee('CRUD');
});

