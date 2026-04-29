<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveUser() && ($user->isAdmin() || $user->isPetugas());
    }

    public function view(User $user, Sale $sale): bool
    {
        if (! $user->isActiveUser()) {
            return false;
        }

        return $user->isAdmin() || $sale->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->isActiveUser() && ($user->isAdmin() || $user->isPetugas());
    }

    public function update(User $user, Sale $sale): bool
    {
        if (! $user->isActiveUser()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPetugas()
            && $sale->user_id === $user->getKey()
            && ! $sale->isLocked()
            && $this->isSameOperationalDay($sale);
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $this->update($user, $sale);
    }

    public function restore(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Sale $sale): bool
    {
        return false;
    }

    private function isSameOperationalDay(Sale $sale): bool
    {
        return CarbonImmutable::parse($sale->date, 'Asia/Jakarta')
            ->isSameDay(now('Asia/Jakarta'));
    }
}
