<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosKantinAdminAuthenticator
{
    public function synchronizeAndResolve(Request $request): ?User
    {
        $configuredEmail = trim((string) config('services.kansor.admin_email'));
        $configuredPassword = (string) config('services.kansor.admin_password');
        $requestEmail = trim((string) $request->input('email'));
        $requestPassword = (string) $request->input('password');

        if ($configuredEmail === '' || $configuredPassword === '') {
            return null;
        }

        if (! hash_equals(Str::lower($configuredEmail), Str::lower($requestEmail))) {
            return null;
        }

        if (! hash_equals($configuredPassword, $requestPassword)) {
            return null;
        }

        $existingUser = User::query()
            ->where('email', $configuredEmail)
            ->first();

        if ($existingUser !== null) {
            $existingUser->fill([
                'password' => $configuredPassword,
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'active' => true,
            ]);

            if (blank($existingUser->name)) {
                $existingUser->name = $this->defaultDisplayName($configuredEmail);
            }

            $existingUser->save();

            return $existingUser;
        }

        return User::create([
            'name' => $this->defaultDisplayName($configuredEmail),
            'email' => $configuredEmail,
            'password' => $configuredPassword,
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'active' => true,
        ]);
    }

    private function defaultDisplayName(string $email): string
    {
        $name = (string) Str::of(Str::before($email, '@'))
            ->replace(['.', '_', '-'], ' ')
            ->title();

        return $name !== '' ? $name : 'KanSor Admin';
    }
}

