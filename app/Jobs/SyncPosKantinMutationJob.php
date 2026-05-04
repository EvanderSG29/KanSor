<?php

namespace App\Jobs;

use App\Exceptions\PosKantinException;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SyncPosKantinMutationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<int, mixed>  $arguments
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $method,
        public array $arguments = [],
        public array $context = [],
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->overlapKey()))->expireAfter(180),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(PosKantinClient $client): void
    {
        if (! method_exists($client, $this->method)) {
            throw new RuntimeException(sprintf('Method sinkronisasi KanSor [%s] tidak ditemukan.', $this->method));
        }

        try {
            $client->{$this->method}(...$this->arguments);
        } catch (PosKantinException $exception) {
            if ($this->shouldSkipUnsupportedEndpoint($exception)) {
                Log::warning('Sinkronisasi KanSor dilewati karena endpoint belum tersedia.', [
                    'method' => $this->method,
                    'arguments' => $this->arguments,
                    'context' => $this->context,
                    'message' => $exception->getMessage(),
                    'todo' => sprintf('Tambahkan endpoint Apps Script untuk action %s.', $this->method),
                ]);

                return;
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Sinkronisasi KanSor gagal.', [
            'method' => $this->method,
            'arguments' => $this->arguments,
            'context' => $this->context,
            'message' => $exception->getMessage(),
        ]);
    }

    private function overlapKey(): string
    {
        return implode(':', array_filter([
            'kansor-sync',
            $this->method,
            (string) ($this->context['entity'] ?? ''),
            (string) ($this->context['id'] ?? ''),
        ]));
    }

    private function shouldSkipUnsupportedEndpoint(PosKantinException $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, [
            'unknown action',
            'unsupported',
            'not supported',
            'tidak dikenal',
            'belum tersedia',
            'invalid action',
        ]);
    }
}

