<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosKantinSyncRun extends Model
{
    protected $table = 'pos_sync_runs';

    protected $fillable = [
        'scope_owner_user_id',
        'trigger',
        'status',
        'started_at',
        'ended_at',
        'summary',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scope_owner_user_id');
    }
}
