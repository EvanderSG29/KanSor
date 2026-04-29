<?php

namespace App\Services\Auth;

use App\Exceptions\PosKantinException;
use App\Models\PosKantinDeviceCredential;
use App\Models\User;
use App\Services\PosKantin\PosKantinSessionClient;
use Illuminate\Support\Facades\DB;

class PosKantinUserAuthenticator
{
    public function __construct(
        protected OfflineLoginService $offlineLoginService,
        protected PosKantinSessionClient $sessionClient,
    ) {}

    /**
     * @return array{success: bool, mode?: string, message?: string, user?: User}
     */
    public function attempt(string $email, string $password): array
    {
        try {
            $remoteLogin = $this->sessionClient->login($email, $password);

            return [
                'success' => true,
                'mode' => 'online',
                'user' => $this->persistRemoteLogin($email, $password, $remoteLogin),
            ];
        } catch (PosKantinException $exception) {
            if (! $exception->isConnectivityFailure() && ($exception->context()['category'] ?? null) !== 'configuration') {
                return [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }

            $user = $this->offlineLoginService->attempt($email, $password);

            if ($user !== null) {
                return [
                    'success' => true,
                    'mode' => 'offline',
                    'user' => $user,
                ];
            }

            return [
                'success' => false,
                'message' => 'Internet tidak tersedia dan akun ini belum siap untuk login offline.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $remoteLogin
     */
    protected function persistRemoteLogin(string $email, string $password, array $remoteLogin): User
    {
        return DB::transaction(function () use ($email, $password, $remoteLogin): User {
            /** @var array<string, mixed> $remoteUser */
            $remoteUser = is_array($remoteLogin['user'] ?? null) ? $remoteLogin['user'] : [];
            $remoteUserId = (string) ($remoteUser['id'] ?? '');

            $userQuery = User::query();

            if ($remoteUserId !== '') {
                $userQuery->where('remote_user_id', $remoteUserId)->orWhere('email', trim($email));
            } else {
                $userQuery->where('email', trim($email));
            }

            $user = $userQuery->first();

            if ($user === null) {
                $user = new User;
            }

            $user->fill([
                'name' => (string) ($remoteUser['fullName'] ?? $email),
                'email' => trim($email),
                'password' => $password,
                'remote_user_id' => $remoteUserId !== '' ? $remoteUserId : $user->remote_user_id,
                'role' => (string) ($remoteUser['role'] ?? 'petugas'),
                'status' => (string) ($remoteUser['status'] ?? 'aktif'),
                'active' => (string) ($remoteUser['status'] ?? 'aktif') === User::STATUS_ACTIVE,
                'remote_auth_updated_at' => (string) ($remoteUser['authUpdatedAt'] ?? ''),
                'offline_login_expires_at' => now()->addDays((int) config('services.pos_kantin.offline_login_days', 30)),
                'last_remote_login_at' => now(),
            ]);
            $user->save();

            $trustedDevice = $this->sessionClient->createTrustedDevice((string) $remoteLogin['token']);

            PosKantinDeviceCredential::query()->updateOrCreate(
                [
                    'scope_owner_user_id' => $user->getKey(),
                ],
                [
                    'remote_user_id' => $remoteUserId,
                    'email' => trim($email),
                    'trusted_device_token' => $trustedDevice['token'] ?? null,
                    'trusted_device_expires_at' => $trustedDevice['expiresAt'] ?? null,
                    'remote_session_token' => $remoteLogin['token'],
                    'remote_session_expires_at' => $remoteLogin['expiresAt'] ?? null,
                    'remote_auth_updated_at' => (string) ($remoteUser['authUpdatedAt'] ?? ''),
                    'device_label' => (string) config('services.pos_kantin.device_label', 'KanSor Desktop'),
                ],
            );

            return $user->fresh();
        });
    }
}
