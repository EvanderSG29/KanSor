<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosKantinSyncConflict extends Model
{
    protected $table = 'pos_sync_conflicts';

    protected $fillable = [
        'scope_owner_user_id',
        'outbox_id',
        'entity_type',
        'entity_remote_id',
        'local_payload',
        'server_payload',
        'resolution_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'local_payload' => 'array',
            'server_payload' => 'array',
        ];
    }

    public function outbox(): BelongsTo
    {
        return $this->belongsTo(PosKantinSyncOutbox::class, 'outbox_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scope_owner_user_id');
    }
}
