<?php

namespace App\Services\PosKantin;

use App\Exceptions\PosKantinException;
use App\Services\PosKantin\Concerns\InteractsWithPosKantinApi;
use Illuminate\Support\Str;

class PosKantinClient
{
    use InteractsWithPosKantinApi;

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
            throw new PosKantinException('Kredensial admin KanSor belum lengkap di environment.');
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
            throw new PosKantinException('Respons login KanSor tidak valid.', [
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
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function listFoods(array $payload = []): array
    {
        $data = $this->request('listFoods', $payload);

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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveTransaction(array $payload): array
    {
        return $this->mutation('saveTransaction', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createTransaction(array $payload): array
    {
        return $this->saveTransaction($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateTransaction(string|int $id, array $payload): array
    {
        return $this->saveTransaction(array_merge($payload, ['id' => $id]));
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteTransaction(string|int $id): array
    {
        return $this->mutation('deleteTransaction', ['id' => $id]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveSupplier(array $payload): array
    {
        return $this->mutation('saveSupplier', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createSupplier(array $payload): array
    {
        return $this->saveSupplier($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateSupplier(string|int $id, array $payload): array
    {
        return $this->saveSupplier(array_merge($payload, ['id' => $id]));
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteSupplier(string|int $id): array
    {
        return $this->mutation('deleteSupplier', ['id' => $id]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveUser(array $payload): array
    {
        return $this->mutation('saveUser', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createUser(array $payload): array
    {
        return $this->saveUser($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateUser(string|int $id, array $payload): array
    {
        return $this->saveUser(array_merge($payload, ['id' => $id]));
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteUser(string|int $id): array
    {
        return $this->mutation('deleteUser', ['id' => $id]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createFood(array $payload): array
    {
        return $this->saveFood($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateFood(string|int $id, array $payload): array
    {
        return $this->saveFood(array_merge($payload, ['id' => $id]));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveFood(array $payload): array
    {
        return $this->mutation('saveFood', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteFood(string|int $id): array
    {
        return $this->mutation('deleteFood', ['id' => $id]);
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function mutation(string $action, array $payload): array
    {
        $data = $this->request($action, $payload);

        return is_array($data) ? $data : [];
    }
}
