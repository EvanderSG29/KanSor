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

test('module pages render backend errors from pos kantin client', function (string $routeName, array $parameters = []) {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'error-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            return Http::response([
                'success' => false,
                'message' => 'Backend POS Kantin sedang tidak tersedia.',
                'data' => null,
            ]);
        },
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route($routeName, $parameters))
        ->assertSuccessful()
        ->assertSee('Backend POS Kantin sedang tidak tersedia.');
})->with([
    'transactions' => ['pos-kantin.transactions.index', []],
    'savings' => ['pos-kantin.savings.index', []],
    'suppliers' => ['pos-kantin.suppliers.index', []],
    'supplier payouts' => ['pos-kantin.supplier-payouts.index', []],
    'users' => ['pos-kantin.users.index', []],
]);
