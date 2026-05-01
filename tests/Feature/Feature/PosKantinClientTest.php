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

test('it can read backend health', function () {
    Http::fake([
        'https://example.test/*' => Http::response([
            'success' => true,
            'message' => 'POS Kantin API aktif.',
            'data' => [
                'appName' => 'KanSor API',
                'version' => '0.1.0',
                'configuredSpreadsheet' => true,
            ],
        ]),
    ]);

    $health = app(PosKantinClient::class)->health();

    expect($health['appName'])->toBe('KanSor API')
        ->and($health['configuredSpreadsheet'])->toBeTrue();
});

test('it caches the service account token between requests', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'cached-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'Ringkasan dashboard berhasil diambil.',
                'data' => [
                    'transactionCount' => 12,
                ],
            ]);
        },
    ]);

    $client = app(PosKantinClient::class);

    expect($client->dashboardSummary()['transactionCount'])->toBe(12)
        ->and($client->dashboardSummary()['transactionCount'])->toBe(12);

    Http::assertSentCount(3);
});

test('it refreshes cached token when backend session expires', function () {
    $callCount = 0;

    Http::fake([
        'https://example.test/*' => function (Request $request) use (&$callCount) {
            $callCount++;

            if (($request['action'] ?? null) === 'login') {
                $token = $callCount === 1 ? 'expired-token' : 'fresh-token';

                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => $token,
                        'expiresAt' => now()->addHour()->toIso8601String(),
                        'user' => ['email' => 'evandersmidgidiin@gmail.com'],
                    ],
                ]);
            }

            if (($request['token'] ?? null) === 'expired-token') {
                return Http::response([
                    'success' => false,
                    'message' => 'Sesi sudah kedaluwarsa.',
                    'data' => null,
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'Ringkasan dashboard berhasil diambil.',
                'data' => [
                    'transactionCount' => 99,
                ],
            ]);
        },
    ]);

    Cache::put('pos_kantin.service_account.token', [
        'token' => 'expired-token',
        'expires_at' => now()->addHour()->toIso8601String(),
    ], now()->addHour());

    $summary = app(PosKantinClient::class)->dashboardSummary();

    expect($summary['transactionCount'])->toBe(99);
});

test('it throws an exception on malformed responses', function () {
    Http::fake([
        'https://example.test/*' => Http::response('not-json', 200),
    ]);

    expect(fn () => app(PosKantinClient::class)->health())
        ->toThrow(PosKantinException::class, 'Respons POS Kantin tidak valid');
});

test('it throws an exception when backend returns success false', function () {
    Http::fake([
        'https://example.test/*' => Http::response([
            'success' => false,
            'message' => 'Spreadsheet belum dikonfigurasi.',
            'data' => null,
        ], 200),
    ]);

    expect(fn () => app(PosKantinClient::class)->health())
        ->toThrow(PosKantinException::class, 'Spreadsheet belum dikonfigurasi.');
});

test('it wraps connection failures in pos kantin exception', function () {
    Http::fake([
        'https://example.test/*' => Http::failedConnection(),
    ]);

    expect(fn () => app(PosKantinClient::class)->health())
        ->toThrow(PosKantinException::class, 'Gagal terhubung ke POS Kantin API saat memanggil action [health].');
});

test('it throws an exception when backend url is missing', function () {
    Config::set('services.pos_kantin.api_url', null);

    expect(fn () => app(PosKantinClient::class)->health())
        ->toThrow(PosKantinException::class, 'POS Kantin API URL belum dikonfigurasi.');
});

test('it can request legacy spreadsheet migration preview', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'migration-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            return Http::response([
                'success' => true,
                'message' => 'Migrasi spreadsheet legacy berhasil diproses.',
                'data' => [
                    'dryRun' => true,
                    'sourceSpreadsheet' => [
                        'id' => 'legacy-sheet-id',
                        'name' => 'Spreadsheet Lama',
                    ],
                    'targetSpreadsheet' => [
                        'id' => 'new-sheet-id',
                        'name' => 'Spreadsheet Baru',
                    ],
                    'sheets' => [
                        [
                            'sheetKey' => 'buyers',
                            'sheetName' => 'buyers',
                            'sourceRowCount' => 20,
                            'targetRowCount' => 0,
                            'mode' => 'overwrite_sheet',
                            'compatible' => true,
                        ],
                    ],
                ],
            ]);
        },
    ]);

    $result = app(PosKantinClient::class)->migrateLegacySpreadsheet([
        'sourceSpreadsheetId' => 'legacy-sheet-id',
        'dryRun' => true,
    ]);

    expect($result['dryRun'])->toBeTrue()
        ->and($result['sourceSpreadsheet']['id'])->toBe('legacy-sheet-id')
        ->and($result['sheets'][0]['sheetKey'])->toBe('buyers');
});
