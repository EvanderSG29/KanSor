<?php

use App\Models\PosKantinSyncConflict;
use App\Models\PosKantinSyncOutbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.kansor.sync_interval_seconds' => 60,
    ]);
});

function seedLocalPosKantinData(User $user): void
{
    seedPosKantinMirror($user, 'users', [[
        'id' => 'USR-001',
        'fullName' => 'Evander Smid Gidiin',
        'nickname' => 'Evander',
        'email' => 'evandersmidgidiin@gmail.com',
        'role' => 'admin',
        'status' => 'aktif',
        'authUpdatedAt' => '2026-04-28T10:00:00.000Z',
        'createdAt' => '2026-04-28T09:00:00.000Z',
        'updatedAt' => '2026-04-28T10:00:00.000Z',
    ]]);

    seedPosKantinMirror($user, 'buyers', [[
        'id' => 'BUY-001',
        'buyerName' => 'Budi',
        'classOrCategory' => 'X PPLG 1',
        'openingBalance' => 0,
        'currentBalance' => 2000,
        'status' => 'aktif',
        'createdAt' => '2026-04-28T09:00:00.000Z',
        'updatedAt' => '2026-04-28T09:10:00.000Z',
    ]]);

    seedPosKantinMirror($user, 'savings', [[
        'id' => 'SAV-001',
        'studentId' => 'STD-001',
        'studentName' => 'Budi',
        'className' => 'X PPLG 1',
        'gender' => 'L',
        'groupName' => '',
        'depositAmount' => 5000,
        'changeBalance' => 2000,
        'recordedAt' => '2026-04-28T08:00:00.000Z',
        'recordedByUserId' => 'USR-001',
        'recordedByName' => 'Evander',
        'notes' => '',
        'createdAt' => '2026-04-28T08:00:00.000Z',
        'updatedAt' => '2026-04-28T08:00:00.000Z',
        'deletedAt' => '',
    ]]);

    seedPosKantinMirror($user, 'suppliers', [[
        'id' => 'SUP-001',
        'supplierName' => 'Kang Latif',
        'contactName' => 'Pak Latif',
        'contactPhone' => '08123',
        'commissionRate' => 10,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 1,
        'notes' => '',
        'isActive' => true,
        'createdAt' => '2026-04-28T07:00:00.000Z',
        'updatedAt' => '2026-04-28T07:00:00.000Z',
    ]]);

    seedPosKantinMirror($user, 'transactions', [
        [
            'id' => 'TRX-001',
            'transactionDate' => now()->format('Y-m-d'),
            'inputByUserId' => 'USR-001',
            'inputByName' => 'Evander',
            'supplierId' => 'SUP-001',
            'supplierName' => 'Kang Latif',
            'itemName' => 'Roti Bakar',
            'unitName' => 'pcs',
            'quantity' => 10,
            'remainingQuantity' => 2,
            'soldQuantity' => 8,
            'costPrice' => 1000,
            'unitPrice' => 2500,
            'grossSales' => 20000,
            'profitAmount' => 12000,
            'commissionRate' => 10,
            'commissionBaseType' => 'revenue',
            'commissionAmount' => 2000,
            'supplierNetAmount' => 18000,
            'payoutTermDays' => 1,
            'payoutDueDate' => now()->format('Y-m-d'),
            'supplierPayoutId' => '',
            'totalValue' => 20000,
            'notes' => '',
            'createdAt' => now()->subHour()->toIso8601String(),
            'updatedAt' => now()->subHour()->toIso8601String(),
            'deletedAt' => '',
            'dueStatus' => 'today',
        ],
        [
            'id' => 'TRX-002',
            'transactionDate' => '2026-04-27',
            'inputByUserId' => 'USR-001',
            'inputByName' => 'Evander',
            'supplierId' => 'SUP-002',
            'supplierName' => 'Uni',
            'itemName' => 'Es Teh',
            'unitName' => 'gelas',
            'quantity' => 10,
            'remainingQuantity' => 2,
            'soldQuantity' => 8,
            'costPrice' => 2000,
            'unitPrice' => 5000,
            'grossSales' => 40000,
            'profitAmount' => 12000,
            'commissionRate' => 10,
            'commissionBaseType' => 'revenue',
            'commissionAmount' => 4000,
            'supplierNetAmount' => 36000,
            'payoutTermDays' => 1,
            'payoutDueDate' => '2026-04-27',
            'supplierPayoutId' => '',
            'totalValue' => 40000,
            'notes' => '',
            'createdAt' => '2026-04-27T10:00:00.000Z',
            'updatedAt' => '2026-04-27T10:00:00.000Z',
            'deletedAt' => '',
            'dueStatus' => 'today',
        ],
    ]);

    seedPosKantinMirror($user, 'changeEntries', [[
        'id' => 'CHG-001',
        'dailyFinanceId' => 'FIN-001',
        'financeDate' => '2026-04-28',
        'buyerId' => 'BUY-001',
        'buyerNameSnapshot' => 'Budi',
        'changeAmount' => 15000,
        'status' => 'belum',
        'settledAt' => '',
        'settledByUserId' => '',
        'settledByName' => '',
        'notes' => '',
        'createdByUserId' => 'USR-001',
        'createdByName' => 'Evander',
        'createdAt' => '2026-04-28T08:10:00.000Z',
        'updatedAt' => '2026-04-28T08:10:00.000Z',
        'deletedAt' => '',
    ]]);

    seedPosKantinMirror($user, 'supplierPayouts', [[
        'id' => 'PAY-001',
        'supplierId' => 'SUP-003',
        'supplierNameSnapshot' => 'Bu Eva',
        'periodStart' => '2026-04-20',
        'periodEnd' => '2026-04-21',
        'dueDate' => '2026-04-22',
        'transactionCount' => 1,
        'totalGrossSales' => 25000,
        'totalProfit' => 5000,
        'totalCommission' => 2000,
        'totalSupplierNetAmount' => 20000,
        'status' => 'paid',
        'paidAt' => '2026-04-22T10:00:00.000Z',
        'paidByUserId' => 'USR-001',
        'paidByName' => 'Evander',
        'notes' => '',
        'createdAt' => '2026-04-22T10:00:00.000Z',
        'updatedAt' => '2026-04-22T10:00:00.000Z',
    ]]);
}

test('dashboard page shows local summary for authenticated users', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    seedLocalPosKantinData($user);

    $this->actingAs($user)
        ->get('/home')
        ->assertSuccessful()
        ->assertSee('Ringkasan operasional')
        ->assertSee('Roti Bakar')
        ->assertSee('Laporan Operasional')
        ->assertSee('Dashboard');
});

test('reports page shows local summary snapshot', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    seedLocalPosKantinData($user);

    $this->actingAs($user)
        ->get(route('kansor.reports.index'))
        ->assertSuccessful()
        ->assertSee('Ringkasan eksekutif')
        ->assertSee('Kelola payout pemasok')
        ->assertSee('Roti Bakar');
});

test('transaction page shows local transaction data', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    seedLocalPosKantinData($user);

    $this->actingAs($user)
        ->get(route('kansor.transactions.index', ['search' => 'Es Teh']))
        ->assertSuccessful()
        ->assertSee('Daftar transaksi')
        ->assertSee('Es Teh')
        ->assertSee('Uni');
});

test('savings page shows local saving data', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    seedLocalPosKantinData($user);

    $this->actingAs($user)
        ->get(route('kansor.savings.index'))
        ->assertSuccessful()
        ->assertSee('Daftar simpanan')
        ->assertSee('Budi');
});

test('supplier page shows local supplier data', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    seedLocalPosKantinData($user);

    $this->actingAs($user)
        ->get(route('kansor.suppliers.index'))
        ->assertSuccessful()
        ->assertSee('Data pemasok server')
        ->assertSee('Kang Latif');
});

test('supplier payout page shows local payout data', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    seedLocalPosKantinData($user);

    $this->actingAs($user)
        ->get(route('kansor.supplier-payouts.index'))
        ->assertSuccessful()
        ->assertSee('Payout Pemasok Outstanding')
        ->assertSee('Bu Eva');
});

test('users page shows local mirrored user data', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    seedLocalPosKantinData($user);

    $this->actingAs($user)
        ->get(route('kansor.users.index'))
        ->assertSuccessful()
        ->assertSee('Data pengguna server')
        ->assertSee('Evander Smid Gidiin');
});

test('sync page shows conflict comparison details and confirmation copy', function () {
    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => 'admin',
        'status' => 'aktif',
        'active' => true,
    ]);

    PosKantinSyncOutbox::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'client_mutation_id' => '55555555-5555-5555-5555-555555555555',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-005',
        'payload' => [
            'id' => 'SUP-005',
            'supplierName' => 'Supplier Lokal',
            'updatedAt' => '2026-04-29T07:45:00.000Z',
        ],
        'expected_updated_at' => '2026-04-29T07:45:00.000Z',
        'status' => 'conflict',
        'last_error' => 'Perubahan lokal bentrok dengan data server.',
        'server_snapshot' => [
            'id' => 'SUP-005',
            'supplierName' => 'Supplier Server',
            'updatedAt' => '2026-04-29T08:00:00.000Z',
        ],
    ]);

    PosKantinSyncConflict::query()->create([
        'scope_owner_user_id' => $user->getKey(),
        'outbox_id' => PosKantinSyncOutbox::query()
            ->where('client_mutation_id', '55555555-5555-5555-5555-555555555555')
            ->value('id'),
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-005',
        'local_payload' => [
            'id' => 'SUP-005',
            'supplierName' => 'Supplier Lokal',
            'updatedAt' => '2026-04-29T07:45:00.000Z',
        ],
        'server_payload' => [
            'id' => 'SUP-005',
            'supplierName' => 'Supplier Server',
            'updatedAt' => '2026-04-29T08:00:00.000Z',
        ],
        'resolution_status' => 'unresolved',
    ]);

    $this->actingAs($user)
        ->get(route('kansor.sync.index'))
        ->assertSuccessful()
        ->assertSee('Konflik sinkronisasi')
        ->assertSee('Field Berbeda')
        ->assertSee('supplierName')
        ->assertSee('Supplier Lokal')
        ->assertSee('Supplier Server')
        ->assertSee('Pakai server')
        ->assertSee('Kirim ulang lokal')
        ->assertSee('Anda akan mengganti data lokal dengan versi server.')
        ->assertSee('Anda akan mengirim ulang data lokal ke server.');
});

test('production layout hides native debug drawer and desktop config', function () {
    config([
        'app.env' => 'production',
        'nativephp-internal.running' => true,
    ]);

    $user = User::factory()->create([
        'remote_user_id' => 'USR-001',
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    seedLocalPosKantinData($user);

    $this->actingAs($user)
        ->get('/home')
        ->assertSuccessful()
        ->assertDontSee('window.KanSorNativeDesktop', false)
        ->assertDontSee('Fruitcake Laravel Debugbar')
        ->assertDontSee('Ctrl+Shift+D lalu F');
});

test('user data stays isolated per local account scope', function () {
    $userA = User::factory()->create([
        'remote_user_id' => 'USR-A',
        'email' => 'a@example.com',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    $userB = User::factory()->create([
        'remote_user_id' => 'USR-B',
        'email' => 'b@example.com',
        'role' => 'admin',
        'status' => 'aktif',
    ]);

    seedPosKantinMirror($userA, 'suppliers', [[
        'id' => 'SUP-A',
        'supplierName' => 'Supplier A',
        'contactName' => '',
        'contactPhone' => '',
        'commissionRate' => 10,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 1,
        'notes' => '',
        'isActive' => true,
        'createdAt' => '2026-04-28T07:00:00.000Z',
        'updatedAt' => '2026-04-28T07:00:00.000Z',
    ]]);

    seedPosKantinMirror($userB, 'suppliers', [[
        'id' => 'SUP-B',
        'supplierName' => 'Supplier B',
        'contactName' => '',
        'contactPhone' => '',
        'commissionRate' => 10,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 1,
        'notes' => '',
        'isActive' => true,
        'createdAt' => '2026-04-28T07:00:00.000Z',
        'updatedAt' => '2026-04-28T07:00:00.000Z',
    ]]);

    $this->actingAs($userA)
        ->get(route('kansor.suppliers.index'))
        ->assertSee('Supplier A')
        ->assertDontSee('Supplier B');

    $this->actingAs($userB)
        ->get(route('kansor.suppliers.index'))
        ->assertSee('Supplier B')
        ->assertDontSee('Supplier A');
});

