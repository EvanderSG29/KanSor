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

            expect($request['action'])->toBe('createSupplier')
                ->and($request['payload']->name)->toBe('Supplier Baru');

            return Http::response([
                'success' => true,
                'message' => 'Supplier berhasil dibuat.',
                'data' => [
                    'id' => 'SUP-NEW',
                ],
            ]);
        },
    ]);

    $result = app(PosKantinClient::class)->createSupplier([
        'name' => 'Supplier Baru',
    ]);

    expect($result['id'])->toBe('SUP-NEW');
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

    expect(fn () => app(PosKantinClient::class)->deleteFood(99))
        ->toThrow(PosKantinException::class, 'Action belum tersedia.');
});
