<?php

namespace App\Models;

use Database\Factories\CanteenTotalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanteenTotal extends Model
{
    /** @use HasFactory<CanteenTotalFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'total_amount',
        'status_iii',
        'status_iv',
        'taken_note',
        'paid_at',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_amount' => 'integer',
            'paid_at' => 'date',
            'paid_amount' => 'integer',
        ];
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }
}
