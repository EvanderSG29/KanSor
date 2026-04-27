<?php

namespace App\Services\PosKantin;

use App\Exceptions\PosKantinException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class PosKantinClient
{
    public function __construct(
        protected PosKantinServiceTokenCache $tokenCache,
    ) {}

    public function configured(): bool
    {
        return $this->apiUrl() !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $this->ensureConfigured();

        $response = $this->sendGet('health', [
            'action' => 'health',
        ]);

        return $this->extractData($response, 'health');
    }

    public function request(string $action, array $payload = [], ?string $token = null): mixed
    {
        $resolvedToken = $token;

        if ($action !== 'login' && $resolvedToken === null) {
            $resolvedToken = $this->tokenCache->get() ?? $this->loginAsServiceAccount();
        }

        try {
            return $this->performRequest($action, $payload, $resolvedToken);
        } catch (PosKantinException $exception) {
            if ($token === null && $action !== 'login' && $this->shouldRefreshToken($exception)) {
                $this->tokenCache->forget();

                return $this->performRequest($action, $payload, $this->loginAsServiceAccount());
            }

            throw $exception;
        }
    }

    public function loginAsServiceAccount(): string
    {
        $this->ensureConfigured();

        $email = (string) config('services.pos_kantin.admin_email');
        $password = (string) config('services.pos_kantin.admin_password');

        if ($email === '' || $password === '') {
            throw new PosKantinException('Kredensial admin POS Kantin belum lengkap di environment.');
        }

        $response = $this->sendPost('login', [
            'action' => 'login',
            'payload' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);

        $data = $this->extractData($response, 'login');

        if (! is_array($data) || ! isset($data['token']) || ! is_string($data['token'])) {
            throw new PosKantinException('Respons login POS Kantin tidak valid.', [
                'action' => 'login',
                'data' => $data,
            ]);
        }

        $this->tokenCache->put($data['token'], is_string($data['expiresAt'] ?? null) ? $data['expiresAt'] : null);

        return $data['token'];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardSummary(): array
    {
        $data = $this->request('dashboardSummary');

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function listTransactions(array $payload = []): array
    {
        $data = $this->request('listTransactions', $payload);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function listSuppliers(array $payload = []): array
    {
        $data = $this->request('listSuppliers', $payload);

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSavings(): array
    {
        $data = $this->request('listSavings');

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function listSupplierPayouts(): array
    {
        $data = $this->request('listSupplierPayouts');

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUsers(): array
    {
        $data = $this->request('listUsers');

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function migrateLegacySpreadsheet(array $payload): array
    {
        $data = $this->request('migrateLegacySpreadsheet', $payload);

        return is_array($data) ? $data : [];
    }

    protected function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new PosKantinException('POS Kantin API URL belum dikonfigurasi.');
        }
    }

    protected function apiUrl(): string
    {
        return trim((string) config('services.pos_kantin.api_url'));
    }

    protected function http(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->connectTimeout((int) config('services.pos_kantin.connect_timeout', 10))
            ->timeout((int) config('services.pos_kantin.timeout', 20))
            ->retry([250, 500], function (Throwable $exception): bool {
                return $exception instanceof ConnectionException;
            }, throw: false);

        $caBundle = trim((string) config('services.pos_kantin.ca_bundle'));

        if ($caBundle !== '') {
            $request = $request->withOptions([
                'verify' => $caBundle,
            ]);
        }

        return $request;
    }

    protected function performRequest(string $action, array $payload = [], ?string $token = null): mixed
    {
        $this->ensureConfigured();

        $body = [
            'action' => $action,
            'payload' => (object) $payload,
        ];

        if ($token !== null) {
            $body['token'] = $token;
        }

        $response = $this->sendPost($action, $body);

        return $this->extractData($response, $action);
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
                sprintf('Gagal terhubung ke POS Kantin API saat memanggil action [%s].', $action),
                [
                    'action' => $action,
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
                sprintf('Gagal terhubung ke POS Kantin API saat memanggil action [%s].', $action),
                [
                    'action' => $action,
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
                sprintf('POS Kantin API merespons status HTTP %s.', $response->status()),
                [
                    'action' => $action,
                    'body' => Str::limit($response->body(), 500),
                    'status' => $response->status(),
                ],
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_key_exists('success', $payload)) {
            throw new PosKantinException('Respons POS Kantin tidak valid atau bukan JSON yang diharapkan.', [
                'action' => $action,
                'body' => Str::limit($response->body(), 500),
            ]);
        }

        if (($payload['success'] ?? false) !== true) {
            throw new PosKantinException((string) ($payload['message'] ?? 'POS Kantin API mengembalikan kegagalan.'), [
                'action' => $action,
                'payload' => $payload,
            ]);
        }

        return $payload;
    }

    protected function shouldRefreshToken(PosKantinException $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, [
            'sesi tidak ditemukan',
            'sesi sudah kedaluwarsa',
            'user sesi tidak aktif',
            'token wajib disertakan',
        ]);
    }
}
