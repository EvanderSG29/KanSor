<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('services.pos_kantin.api_url', 'https://example.test/macros/s/api/exec');
    Config::set('services.pos_kantin.admin_email', 'evandersmidgidiin@gmail.com');
    Config::set('services.pos_kantin.admin_password', 'secret-password');
    Config::set('services.pos_kantin.legacy_spreadsheet_id', 'legacy-sheet-from-config');
    Http::preventStrayRequests();
});

test('legacy spreadsheet migration command previews migration by default', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'preview-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            $payload = (array) $request['payload'];

            expect($request['action'])->toBe('migrateLegacySpreadsheet')
                ->and($payload['sourceSpreadsheetId'])->toBe('legacy-sheet-from-config')
                ->and($payload['dryRun'])->toBeTrue()
                ->and($payload['includeUsers'])->toBeFalse()
                ->and($payload['allowWithoutBackups'])->toBeFalse();

            return Http::response([
                'success' => true,
                'message' => 'Migrasi spreadsheet legacy berhasil diproses.',
                'data' => [
                    'sourceSpreadsheet' => [
                        'name' => 'Spreadsheet Lama',
                    ],
                    'targetSpreadsheet' => [
                        'name' => 'Spreadsheet Baru',
                    ],
                    'warnings' => [
                        'Referensi user lama akan diputus dari data histori, tetapi snapshot nama tetap dipertahankan.',
                    ],
                    'sheets' => [
                        [
                            'sheetName' => 'buyers',
                            'mode' => 'overwrite_sheet',
                            'sourceRowCount' => 10,
                            'targetRowCount' => 0,
                            'compatible' => true,
                        ],
                    ],
                ],
            ]);
        },
    ]);

    $this->artisan('pos-kantin:migrate-legacy-spreadsheet')
        ->expectsOutputToContain('Mode: preview')
        ->expectsOutputToContain('Preview selesai')
        ->assertExitCode(0);
});

test('legacy spreadsheet migration command can commit with explicit options', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'commit-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            $payload = (array) $request['payload'];

            expect($request['action'])->toBe('migrateLegacySpreadsheet')
                ->and($payload['sourceSpreadsheetId'])->toBe('legacy-sheet-explicit')
                ->and($payload['dryRun'])->toBeFalse()
                ->and($payload['includeUsers'])->toBeTrue()
                ->and($payload['allowWithoutBackups'])->toBeTrue();

            return Http::response([
                'success' => true,
                'message' => 'Migrasi spreadsheet legacy berhasil diproses.',
                'data' => [
                    'sourceSpreadsheet' => [
                        'name' => 'Spreadsheet Lama',
                    ],
                    'targetSpreadsheet' => [
                        'name' => 'Spreadsheet Baru',
                    ],
                    'warnings' => [],
                    'backups' => [
                        'source' => [
                            'ok' => true,
                            'url' => 'https://drive.google.com/source-backup',
                        ],
                        'target' => [
                            'ok' => true,
                            'url' => 'https://drive.google.com/target-backup',
                        ],
                    ],
                    'sheets' => [
                        [
                            'sheetName' => 'buyers',
                            'mode' => 'overwrite_sheet',
                            'sourceRowCount' => 10,
                            'targetRowCount' => 1,
                            'compatible' => true,
                        ],
                    ],
                ],
            ]);
        },
    ]);

    $this->artisan('pos-kantin:migrate-legacy-spreadsheet', [
        '--source' => 'legacy-sheet-explicit',
        '--commit' => true,
        '--include-users' => true,
        '--allow-without-backups' => true,
    ])
        ->expectsOutputToContain('Mode: commit')
        ->expectsOutputToContain('Migrasi selesai.')
        ->assertExitCode(0);
});

test('legacy spreadsheet migration command fails when source spreadsheet is missing', function () {
    Config::set('services.pos_kantin.legacy_spreadsheet_id', null);

    $this->artisan('pos-kantin:migrate-legacy-spreadsheet')
        ->expectsOutputToContain('Spreadsheet sumber belum diisi.')
        ->assertExitCode(1);
});

test('legacy spreadsheet migration command explains when deployed apps script is outdated', function () {
    Http::fake([
        'https://example.test/*' => function (Request $request) {
            if (($request['action'] ?? null) === 'login') {
                return Http::response([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'data' => [
                        'token' => 'stale-deployment-token',
                        'expiresAt' => now()->addHour()->toIso8601String(),
                    ],
                ]);
            }

            return Http::response([
                'success' => false,
                'message' => 'Action tidak dikenal: migrateLegacySpreadsheet',
                'data' => null,
            ]);
        },
    ]);

    $this->artisan('pos-kantin:migrate-legacy-spreadsheet')
        ->expectsOutputToContain('Deployment Web App Apps Script yang dipakai POS_KANTIN_API_URL masih versi lama')
        ->expectsOutputToContain('push source apps-script terbaru')
        ->assertExitCode(1);
});
