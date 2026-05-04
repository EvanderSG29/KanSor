<?php

namespace App\Services\PosKantin;

use App\Exceptions\PosKantinException;
use App\Services\PosKantin\Concerns\InteractsWithPosKantinApi;

class PosKantinSessionClient
{
    use InteractsWithPosKantinApi;

    public function configured(): bool
    {
        return $this->apiUrl() !== '';
    }

    /**
     * @return array{token: string, expiresAt: string, user: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        return $this->request('login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * @return array{token: string, expiresAt: string, user: array<string, mixed>}
     */
    public function loginWithTrustedDevice(string $trustedDeviceToken): array
    {
        return $this->request('loginWithTrustedDevice', [
            'trustedDeviceToken' => $trustedDeviceToken,
        ]);
    }

    /**
     * @return array{token: string, expiresAt: string, user: array<string, mixed>}
     */
    public function createTrustedDevice(string $sessionToken, ?string $deviceLabel = null): array
    {
        return $this->request('createTrustedDevice', [
            'deviceLabel' => $deviceLabel ?: (string) config('services.kansor.device_label', 'KanSor Desktop'),
        ], $sessionToken);
    }

    /**
     * @return array{user: array<string, mixed>, expiresAt: string}
     */
    public function getCurrentUser(string $sessionToken): array
    {
        return $this->request('getCurrentUser', [], $sessionToken);
    }

    /**
     * @param  array<string, string|null>  $since
     * @return array<string, mixed>
     */
    public function syncPull(string $sessionToken, array $since): array
    {
        return $this->request('syncPull', [
            'since' => (object) $since,
        ], $sessionToken);
    }

    /**
     * @param  array<int, array<string, mixed>>  $mutations
     * @return array{results: array<int, array<string, mixed>>}
     */
    public function syncPush(string $sessionToken, array $mutations): array
    {
        return $this->request('syncPush', [
            'mutations' => array_values($mutations),
        ], $sessionToken);
    }

    public function logout(string $sessionToken): void
    {
        $this->request('logout', [], $sessionToken);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function request(string $action, array $payload = [], ?string $sessionToken = null): array
    {
        $this->ensureConfigured();

        $body = [
            'action' => $action,
            'payload' => (object) $payload,
        ];

        if ($sessionToken !== null) {
            $body['token'] = $sessionToken;
        }

        $response = $this->sendPost($action, $body);
        $data = $this->extractData($response, $action);

        if (! is_array($data)) {
            throw new PosKantinException(sprintf('Respons action [%s] tidak valid.', $action), [
                'action' => $action,
                'category' => 'malformed_response',
                'data' => $data,
            ]);
        }

        return $data;
    }
}

