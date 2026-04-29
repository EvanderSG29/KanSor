<?php

namespace App\Services\Auth;

use App\Models\PosKantinDeviceCredential;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OfflineLoginService
{
    public function attempt(string $email, string $password): ?User
    {
        $user = User::query()
            ->where('email', trim($email))
            ->first();

        if ($user === null || ! $user->canUseOfflineLogin()) {
            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        $credential = PosKantinDeviceCredential::query()
            ->whereBelongsTo($user, 'user')
            ->first();

        if ($credential === null || $credential->trusted_device_expires_at === null || $credential->trusted_device_expires_at->isPast()) {
            return null;
        }

        return $user;
    }

    /**
     * @return array<int, array{name: string, email: string, expiresAt: string|null}>
     */
    public function trustedAccounts(): array
    {
        return User::query()
            ->with('deviceCredential')
            ->active()
            ->whereNotNull('offline_login_expires_at')
            ->get()
            ->filter(function (User $user): bool {
                return $user->canUseOfflineLogin()
                    && $user->deviceCredential !== null
                    && $user->deviceCredential->trusted_device_expires_at !== null
                    && $user->deviceCredential->trusted_device_expires_at->isFuture();
            })
            ->map(function (User $user): array {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'expiresAt' => optional($user->offline_login_expires_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
