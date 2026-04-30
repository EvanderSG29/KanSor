<?php

namespace App\Services\PosKantin;

use App\Models\User;
use Illuminate\Support\Str;

class UserSyncPayloadFactory
{
    /**
     * @return array{
     *     id: string,
     *     fullName: string,
     *     nickname: string,
     *     email: string,
     *     role: string,
     *     status: string,
     *     classGroup: string,
     *     notes: string,
     *     password?: string
     * }
     */
    public function make(User $user, ?string $plainTextPassword = null): array
    {
        $normalizedName = Str::of($user->name)
            ->squish()
            ->toString();
        $fallbackName = Str::of($user->email)
            ->before('@')
            ->trim()
            ->toString();
        $fullName = $normalizedName !== '' ? $normalizedName : $fallbackName;

        $nickname = $this->resolveNickname($fullName, $user->email);

        $payload = [
            'id' => (string) $user->getKey(),
            'fullName' => $fullName,
            'nickname' => $nickname,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'classGroup' => '',
            'notes' => '',
        ];

        if (is_string($plainTextPassword) && $plainTextPassword !== '') {
            $payload['password'] = $plainTextPassword;
        }

        return $payload;
    }

    private function resolveNickname(string $fullName, string $email): string
    {
        $firstWord = Str::of($fullName)
            ->before(' ')
            ->trim()
            ->toString();

        if ($firstWord !== '') {
            return $firstWord;
        }

        return Str::of($email)
            ->before('@')
            ->trim()
            ->toString();
    }
}
