<?php

use App\Exceptions\PosKantinException;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('services.pos_kantin.api_url', 'https://example.test/macros/s/api/exec');
    Config::set('services.pos_kantin.admin_email', 'evandersmidgidiin@gmail.com');
    Config::set('services.pos_kantin.admin_password', 'secret-password');
    Config::set('services.pos_kantin.timeout', 20);
    Config::set('services.pos_kantin.connect_timeout', 10);
    Cache::flush();
    Http::preventStrayRequests();
});

test('client mutation wrapper can create supplier via pos kantin api', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'service-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            expect($request['action'])->toBe('saveSupplier')
                ->and($request['payload']->supplierName)->toBe('Supplier Baru');

            return Http::response([
                'success' => true,
                'message' => 'Supplier berhasil disimpan.',
                'data' => [
                    'id' => 'SUP-NEW',
                ],
            ]);
        },
    ]);

    $result = app(PosKantinClient::class)->saveSupplier([
        'supplierName' => 'Supplier Baru',
        'contactName' => 'Pak Supplier',
        'contactPhone' => '08123',
        'commissionRate' => 10,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 0,
        'isActive' => true,
    ]);

    expect($result['id'])->toBe('SUP-NEW');
});

test('client mutation wrapper uses final save food action', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'service-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            expect($request['action'])->toBe('saveFood')
                ->and($request['payload']->name)->toBe('Bakwan')
                ->and($request['payload']->supplierId)->toBe('SUP-001');

            return Http::response([
                'success' => true,
                'message' => 'Makanan berhasil disimpan.',
                'data' => [
                    'id' => 'FOD-NEW',
                ],
            ]);
        },
    ]);

    $result = app(PosKantinClient::class)->saveFood([
        'supplierId' => 'SUP-001',
        'name' => 'Bakwan',
        'unit' => 'pcs',
        'defaultPrice' => 2500,
        'isActive' => true,
    ]);

    expect($result['id'])->toBe('FOD-NEW');
});

test('client mutation wrapper uses final save user action', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'service-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            expect($request['action'])->toBe('saveUser')
                ->and($request['payload']->fullName)->toBe('Petugas Baru')
                ->and($request['payload']->nickname)->toBe('Petugas');

            return Http::response([
                'success' => true,
                'message' => 'User berhasil disimpan.',
                'data' => [
                    'id' => 'USR-NEW',
                ],
            ]);
        },
    ]);

    $result = app(PosKantinClient::class)->saveUser([
        'fullName' => 'Petugas Baru',
        'nickname' => 'Petugas',
        'email' => 'petugas-baru@example.com',
        'role' => 'petugas',
        'status' => 'aktif',
        'password' => 'rahasia123',
    ]);

    expect($result['id'])->toBe('USR-NEW');
});

test('client mutation wrapper uses final save transaction action', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'service-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            expect($request['action'])->toBe('saveTransaction')
                ->and($request['payload']->transactionDate)->toBe('2026-04-29')
                ->and($request['payload']->foodId)->toBe('FOD-001');

            return Http::response([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan.',
                'data' => [
                    'id' => 'TRX-NEW',
                ],
            ]);
        },
    ]);

    $result = app(PosKantinClient::class)->saveTransaction([
        'transactionDate' => '2026-04-29',
        'supplierId' => 'SUP-001',
        'foodId' => 'FOD-001',
        'itemName' => 'Bakwan',
        'unitName' => 'pcs',
        'quantity' => 10,
        'remainingQuantity' => 2,
        'unitPrice' => 5000,
        'costPrice' => 4000,
    ]);

    expect($result['id'])->toBe('TRX-NEW');
});

test('client mutation wrapper surfaces backend failure', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'service-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            return Http::response([
                'success' => false,
                'message' => 'Action belum tersedia.',
                'data' => null,
            ]);
        },
    ]);

    expect(fn () => app(PosKantinClient::class)->saveSupplier([
        'supplierName' => 'Supplier Baru',
        'contactName' => 'Pak Supplier',
        'contactPhone' => '08123',
        'commissionRate' => 10,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 0,
        'isActive' => true,
    ]))
        ->toThrow(PosKantinException::class, 'Action belum tersedia.');
});
