<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PETUGAS = 'petugas';

    public const STATUS_ACTIVE = 'aktif';

    public const STATUS_INACTIVE = 'nonaktif';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'remote_user_id',
        'role',
        'status',
        'active',
        'remote_auth_updated_at',
        'offline_login_expires_at',
        'last_remote_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'active' => 'boolean',
            'offline_login_expires_at' => 'datetime',
            'last_remote_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $active = $user->getAttribute('active');

            if ($user->isDirty('status') && ! $user->isDirty('active')) {
                $user->active = $user->status === self::STATUS_ACTIVE;
                $active = $user->active;
            }

            if ($user->isDirty('active') && ! $user->isDirty('status')) {
                $user->status = $user->active ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
            }

            if ($user->status === null || $user->status === '') {
                $user->status = ($active ?? true) ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
            }

            if ($active === null) {
                $user->active = $user->status === self::STATUS_ACTIVE;
            }
        });
    }

    public function isPosAdmin(): bool
    {
        return $this->isAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPetugas(): bool
    {
        return $this->role === self::ROLE_PETUGAS;
    }

    public function isActiveUser(): bool
    {
        return $this->active === true && $this->status === self::STATUS_ACTIVE;
    }

    public function canUseOfflineLogin(): bool
    {
        return $this->isActiveUser()
            && $this->offline_login_expires_at !== null
            && $this->offline_login_expires_at->isFuture();
    }

    public function deviceCredential(): HasOne
    {
        return $this->hasOne(PosKantinDeviceCredential::class, 'scope_owner_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopePetugas(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_PETUGAS);
    }
}
