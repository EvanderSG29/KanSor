<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PosKantinSyncOutbox extends Model
{
    protected $table = 'pos_sync_outbox';

    protected $fillable = [
        'scope_owner_user_id',
        'client_mutation_id',
        'action',
        'entity_type',
        'entity_remote_id',
        'payload',
        'expected_updated_at',
        'status',
        'attempts',
        'last_error',
        'server_snapshot',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'server_snapshot' => 'array',
        ];
    }

    public function conflict(): HasOne
    {
        return $this->hasOne(PosKantinSyncConflict::class, 'outbox_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scope_owner_user_id');
    }
}
