<?php

namespace App\Services\PosKantin;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class PosKantinServiceTokenCache
{
    public function get(): ?string
    {
        $payload = Cache::memo()->get($this->cacheKey());

        if (! is_array($payload) || ! isset($payload['token']) || ! is_string($payload['token'])) {
            return null;
        }

        $expiresAt = $payload['expires_at'] ?? null;

        if (is_string($expiresAt) && CarbonImmutable::parse($expiresAt)->isPast()) {
            $this->forget();

            return null;
        }

        return $payload['token'];
    }

    public function put(string $token, ?string $expiresAt = null): void
    {
        $ttl = now()->addHours(6);

        if ($expiresAt !== null) {
            $parsedExpiresAt = CarbonImmutable::parse($expiresAt)->subMinute();
            $ttl = $parsedExpiresAt->isFuture() ? $parsedExpiresAt : now()->addMinute();
        }

        Cache::memo()->put($this->cacheKey(), [
            'token' => $token,
            'expires_at' => $expiresAt,
        ], $ttl);
    }

    public function forget(): void
    {
        Cache::memo()->forget($this->cacheKey());
    }

    protected function cacheKey(): string
    {
        return (string) config('services.kansor.token_cache_key', 'kansor.service_account.token');
    }
}

