<?php

namespace App\Services\PosKantin\Concerns;

use App\Exceptions\PosKantinException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

trait InteractsWithPosKantinApi
{
    protected function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new PosKantinException('KanSor API URL belum dikonfigurasi.', [
                'category' => 'configuration',
            ]);
        }
    }

    protected function apiUrl(): string
    {
        return trim((string) config('services.kansor.api_url'));
    }

    protected function http(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->connectTimeout((int) config('services.kansor.connect_timeout', 10))
            ->timeout((int) config('services.kansor.timeout', 20))
            ->retry([250, 500], function (Throwable $exception): bool {
                return $exception instanceof ConnectionException;
            }, throw: false);

        $caBundle = trim((string) config('services.kansor.ca_bundle'));

        if ($caBundle !== '') {
            $request = $request->withOptions([
                'verify' => $caBundle,
            ]);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function sendGet(string $action, array $query): Response
    {
        try {
            return $this->http()->get($this->apiUrl(), $query);
        } catch (ConnectionException $exception) {
            throw new PosKantinException(
                sprintf('Gagal terhubung ke KanSor API saat memanggil action [%s].', $action),
                [
                    'action' => $action,
                    'category' => 'connection',
                    'error' => $exception->getMessage(),
                ],
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function sendPost(string $action, array $body): Response
    {
        try {
            return $this->http()->post($this->apiUrl(), $body);
        } catch (ConnectionException $exception) {
            throw new PosKantinException(
                sprintf('Gagal terhubung ke KanSor API saat memanggil action [%s].', $action),
                [
                    'action' => $action,
                    'category' => 'connection',
                    'error' => $exception->getMessage(),
                ],
                $exception,
            );
        }
    }

    protected function extractData(Response $response, string $action): mixed
    {
        $payload = $this->decodePayload($response, $action);

        return $payload['data'] ?? null;
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed}
     */
    protected function decodePayload(Response $response, string $action): array
    {
        if ($response->failed()) {
            throw new PosKantinException(
                sprintf('KanSor API merespons status HTTP %s.', $response->status()),
                [
                    'action' => $action,
                    'category' => 'http',
                    'body' => Str::limit($response->body(), 500),
                    'status' => $response->status(),
                ],
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_key_exists('success', $payload)) {
            throw new PosKantinException('Respons KanSor tidak valid atau bukan JSON yang diharapkan.', [
                'action' => $action,
                'category' => 'malformed_response',
                'body' => Str::limit($response->body(), 500),
            ]);
        }

        if (($payload['success'] ?? false) !== true) {
            throw new PosKantinException((string) ($payload['message'] ?? 'KanSor API mengembalikan kegagalan.'), [
                'action' => $action,
                'category' => $this->resolvePayloadFailureCategory((string) ($payload['message'] ?? '')),
                'payload' => $payload,
            ]);
        }

        return $payload;
    }

    protected function resolvePayloadFailureCategory(string $message): string
    {
        $normalizedMessage = Str::lower($message);

        if (Str::contains($normalizedMessage, [
            'email atau password tidak cocok',
            'token perangkat wajib diisi',
            'info login perangkat sudah tidak berlaku',
            'sesi tidak ditemukan',
            'sesi sudah kedaluwarsa',
            'user sesi tidak aktif',
            'token wajib disertakan',
        ])) {
            return 'authentication';
        }

        return 'application';
    }
}

