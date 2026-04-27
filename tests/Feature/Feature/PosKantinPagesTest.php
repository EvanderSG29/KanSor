<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('services.pos_kantin.api_url', 'https://example.test/macros/s/api/exec');
    Config::set('services.pos_kantin.admin_email', 'evandersmidgidiin@gmail.com');
    Config::set('services.pos_kantin.admin_password', 'secret-password');
    Cache::flush();
    Http::preventStrayRequests();
});

function fakePosKantinDashboard(): void
{
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'dashboard-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            if (($request['action'] ?? null) === 'dashboardSummary') {
                return Http::response([
                    'success' => true,
                    'message' => 'Ringkasan dashboard berhasil diambil.',
                    'data' => [
                        'todayTransactionCount' => 4,
                        'todayGrossSales' => 150000,
                        'activeSuppliers' => 3,
                        'overdueSupplierPayoutCount' => 1,
                        'transactionCount' => 22,
                        'totalGrossSales' => 800000,
                        'totalProfit' => 120000,
                        'totalCommission' => 45000,
                        'userCount' => 2,
                        'activeBuyerCount' => 5,
                        'savingsCount' => 8,
                        'pendingChangeAmount' => 15000,
                        'recentTransactions' => [
                            [
                                'transactionDate' => '2026-04-27',
                                'supplierName' => 'Kang Latif',
                                'itemName' => 'Roti Bakar',
                                'grossSales' => 20000,
                                'supplierNetAmount' => 18000,
                            ],
                        ],
                        'outstandingPayoutBuckets' => [
                            [
                                'payoutTermDays' => 1,
                                'totalSupplierNetAmount' => 18000,
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'POS Kantin API aktif.',
                'data' => [
                    'appName' => 'KanSor POS Kantin API',
                    'version' => '0.1.0',
                    'configuredSpreadsheet' => true,
                ],
            ]);
        },
    ]);
}

test('dashboard page shows backend summary for authenticated users', function () {
    fakePosKantinDashboard();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/home')
        ->assertSuccessful()
        ->assertSee('Ringkasan integrasi backend POS Kantin')
        ->assertSee('KanSor POS Kantin API')
        ->assertSee('Roti Bakar')
        ->assertSee('Laporan')
        ->assertSee('Dashboard POS');
});

test('reports page shows summary snapshot', function () {
    fakePosKantinDashboard();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pos-kantin.reports.index'))
        ->assertSuccessful()
        ->assertSee('Ringkasan eksekutif')
        ->assertSee('Kelola pembayaran supplier')
        ->assertSee('Roti Bakar');
});

test('transaction page shows transaction data', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'transactions-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'Daftar transaksi berhasil diambil.',
                'data' => [
                    'items' => [
                        [
                            'transactionDate' => '2026-04-27',
                            'supplierName' => 'Uni',
                            'itemName' => 'Es Teh',
                            'unitName' => 'gelas',
                            'quantity' => 10,
                            'soldQuantity' => 8,
                            'grossSales' => 40000,
                            'supplierNetAmount' => 36000,
                            'dueStatus' => 'today',
                        ],
                    ],
                    'summary' => [
                        'rowCount' => 1,
                        'totalGrossSales' => 40000,
                        'totalProfit' => 12000,
                        'unsettledSupplierNetAmount' => 36000,
                    ],
                    'pagination' => [
                        'page' => 1,
                        'pageSize' => 10,
                        'totalItems' => 1,
                        'startItem' => 1,
                        'endItem' => 1,
                        'hasPrev' => false,
                        'hasNext' => false,
                    ],
                ],
            ]);
        },
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pos-kantin.transactions.index', ['search' => 'Es Teh']))
        ->assertSuccessful()
        ->assertSee('Daftar transaksi')
        ->assertSee('Es Teh')
        ->assertSee('Uni');
});

test('savings page shows saving data', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'savings-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'Data simpanan berhasil diambil.',
                'data' => [
                    [
                        'studentName' => 'Budi',
                        'className' => 'X PPLG 1',
                        'depositAmount' => 5000,
                        'changeBalance' => 2000,
                        'recordedByName' => 'Evander',
                        'recordedAt' => '2026-04-27T08:00:00.000Z',
                    ],
                ],
            ]);
        },
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pos-kantin.savings.index'))
        ->assertSuccessful()
        ->assertSee('Daftar simpanan')
        ->assertSee('Budi');
});

test('supplier page shows supplier data', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'suppliers-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'Daftar pemasok berhasil diambil.',
                'data' => [
                    [
                        'supplierName' => 'Kang Latif',
                        'contactName' => 'Pak Latif',
                        'contactPhone' => '08123',
                        'commissionRate' => 10,
                        'payoutTermDays' => 1,
                        'isActive' => true,
                    ],
                ],
            ]);
        },
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pos-kantin.suppliers.index'))
        ->assertSuccessful()
        ->assertSee('Master pemasok')
        ->assertSee('Kang Latif');
});

test('supplier payout page shows payout data', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'payouts-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'Data pembayaran pemasok berhasil diambil.',
                'data' => [
                    'summary' => [
                        'outstandingCount' => 1,
                        'dueCount' => 1,
                        'overdueCount' => 0,
                        'settledAmount' => 20000,
                    ],
                    'outstanding' => [
                        [
                            'supplierName' => 'Bu Eva',
                            'periodStart' => '2026-04-26',
                            'periodEnd' => '2026-04-27',
                            'dueDate' => '2026-04-27',
                            'transactionCount' => 3,
                            'totalSupplierNetAmount' => 45000,
                            'dueStatus' => 'today',
                        ],
                    ],
                    'history' => [
                        [
                            'supplierNameSnapshot' => 'Bu Eva',
                            'periodStart' => '2026-04-20',
                            'periodEnd' => '2026-04-21',
                            'paidAt' => '2026-04-22T10:00:00.000Z',
                            'totalSupplierNetAmount' => 20000,
                            'paidByName' => 'Evander',
                        ],
                    ],
                ],
            ]);
        },
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pos-kantin.supplier-payouts.index'))
        ->assertSuccessful()
        ->assertSee('Outstanding payout')
        ->assertSee('Bu Eva');
});

test('users page shows backend user data', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'users-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'Daftar user berhasil diambil.',
                'data' => [
                    [
                        'fullName' => 'Evander Smid Gidiin',
                        'nickname' => 'Evander',
                        'email' => 'evandersmidgidiin@gmail.com',
                        'role' => 'admin',
                        'status' => 'aktif',
                        'updatedAt' => '2026-04-27T10:00:00.000Z',
                    ],
                ],
            ]);
        },
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pos-kantin.users.index'))
        ->assertSuccessful()
        ->assertSee('Daftar pengguna backend')
        ->assertSee('Evander Smid Gidiin');
});
