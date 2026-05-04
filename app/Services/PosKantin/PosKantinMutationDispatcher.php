<?php

namespace App\Services\PosKantin;

use App\Jobs\SyncPosKantinMutationJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Throwable;

class PosKantinMutationDispatcher
{
    /**
     * @var list<string>
     */
    private const UNSUPPORTED_MUTATION_METHODS = [
        'createFood',
        'updateFood',
        'deleteFood',
        'createSupplier',
        'updateSupplier',
        'deleteSupplier',
        'createTransaction',
        'updateTransaction',
        'createUser',
        'updateUser',
        'deleteUser',
    ];

    /**
     * @param  array<int, mixed>  $arguments
     * @param  array<string, mixed>  $context
     * @return array{status: 'applied'|'queued'|'unsupported'|'failed', message: string, warning: string|null}
     */
    public function dispatch(string $method, array $arguments, array $context = []): array
    {
        if (! app(PosKantinClient::class)->configured()) {
            Log::warning('Sinkronisasi KanSor gagal dijadwalkan karena API belum dikonfigurasi.', [
                'method' => $method,
                'context' => $context,
            ]);

            return [
                'status' => 'failed',
                'message' => 'Data lokal berhasil disimpan, tetapi sinkronisasi spreadsheet gagal dijalankan karena KanSor API belum dikonfigurasi.',
                'warning' => null,
            ];
        }

        if (in_array($method, self::UNSUPPORTED_MUTATION_METHODS, true)) {
            Log::warning('Sinkronisasi KanSor dilewati karena alur mutasi lokal belum kompatibel dengan Apps Script aktif.', [
                'method' => $method,
                'context' => $context,
                'todo' => 'Siapkan adapter payload atau endpoint Apps Script yang sesuai sebelum mengaktifkan sinkronisasi langsung.',
            ]);

            return [
                'status' => 'unsupported',
                'message' => 'Data lokal berhasil disimpan, tetapi belum tersinkron ke spreadsheet karena endpoint Apps Script untuk perubahan ini belum siap.',
                'warning' => 'Data lokal berhasil disimpan, tetapi belum tersinkron ke spreadsheet karena endpoint Apps Script untuk perubahan ini belum siap.',
            ];
        }

        try {
            SyncPosKantinMutationJob::dispatch($method, $arguments, $context)->afterCommit();
        } catch (Throwable $exception) {
            Log::error('Sinkronisasi KanSor gagal dijalankan.', [
                'method' => $method,
                'arguments' => $arguments,
                'context' => $context,
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'message' => 'Data lokal berhasil disimpan, tetapi sinkronisasi spreadsheet gagal dijalankan. Cek log KanSor untuk detail error.',
                'warning' => null,
            ];
        }

        if ($this->dispatchesSynchronously()) {
            Log::info('Sinkronisasi KanSor langsung diterapkan.', [
                'method' => $method,
                'context' => $context,
            ]);

            return [
                'status' => 'applied',
                'message' => 'Data lokal berhasil disimpan dan sinkronisasi spreadsheet langsung diterapkan.',
                'warning' => null,
            ];
        }

        Log::info('Sinkronisasi KanSor masuk antrean.', [
            'method' => $method,
            'context' => $context,
        ]);

        return [
            'status' => 'queued',
            'message' => 'Data lokal berhasil disimpan dan sinkronisasi spreadsheet sudah masuk antrean.',
            'warning' => null,
        ];
    }

    private function dispatchesSynchronously(): bool
    {
        if (app('queue') instanceof QueueFake) {
            return false;
        }

        if (DB::transactionLevel() > 0) {
            return false;
        }

        $connectionName = (string) config('queue.default');
        $driver = (string) config("queue.connections.{$connectionName}.driver");

        return $driver === 'sync';
    }
}
