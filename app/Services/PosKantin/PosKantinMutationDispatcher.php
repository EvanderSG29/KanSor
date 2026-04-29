<?php

namespace App\Services\PosKantin;

use App\Jobs\SyncPosKantinMutationJob;
use Illuminate\Support\Facades\Log;

class PosKantinMutationDispatcher
{
    /**
     * @param  array<int, mixed>  $arguments
     * @param  array<string, mixed>  $context
     */
    public function dispatch(string $method, array $arguments, array $context = []): void
    {
        if (! app(PosKantinClient::class)->configured()) {
            Log::info('Sinkronisasi POS Kantin dilewati karena API belum dikonfigurasi.', [
                'method' => $method,
                'context' => $context,
            ]);

            return;
        }

        SyncPosKantinMutationJob::dispatch($method, $arguments, $context)->afterCommit();
    }
}
