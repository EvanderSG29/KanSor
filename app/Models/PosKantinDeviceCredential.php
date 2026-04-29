<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosKantinDeviceCredential extends Model
{
    protected $table = 'pos_device_credentials';

    protected $fillable = [
        'scope_owner_user_id',
        'remote_user_id',
        'email',
        'trusted_device_token',
        'trusted_device_expires_at',
        'remote_session_token',
        'remote_session_expires_at',
        'remote_auth_updated_at',
        'last_remote_sync_at',
        'device_label',
    ];

    protected $hidden = [
        'trusted_device_token',
        'remote_session_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trusted_device_token' => 'encrypted',
            'trusted_device_expires_at' => 'datetime',
            'remote_session_token' => 'encrypted',
            'remote_session_expires_at' => 'datetime',
            'last_remote_sync_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scope_owner_user_id');
    }
}
