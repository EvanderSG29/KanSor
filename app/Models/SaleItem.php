<?php

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'food_id',
        'unit',
        'quantity',
        'leftover',
        'price_per_unit',
        'total_item',
        'cut_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'leftover' => 'integer',
            'price_per_unit' => 'integer',
            'total_item' => 'integer',
            'cut_amount' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class)->withTrashed();
    }
}
