<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'menunggu';

    public const STATUS_SUPPLIER_PAID = 'dibayar';

    public const STATUS_CANTEEN_DEPOSITED = 'disetor';

    protected $fillable = [
        'date',
        'supplier_id',
        'user_id',
        'additional_users',
        'total_supplier',
        'total_canteen',
        'status_i',
        'status_ii',
        'taken_note',
        'paid_at',
        'paid_amount',
        'supplier_paid_at',
        'supplier_paid_amount',
        'supplier_payment_note',
        'supplier_payment_confirmed_by',
        'canteen_deposited_at',
        'canteen_deposited_amount',
        'canteen_deposit_note',
        'canteen_deposit_confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'additional_users' => 'array',
            'total_supplier' => 'integer',
            'total_canteen' => 'integer',
            'paid_at' => 'date',
            'paid_amount' => 'integer',
            'supplier_paid_at' => 'date',
            'supplier_paid_amount' => 'integer',
            'canteen_deposited_at' => 'date',
            'canteen_deposited_amount' => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplierPaymentConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_payment_confirmed_by');
    }

    public function canteenDepositConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canteen_deposit_confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->where('status_i', self::STATUS_PENDING)
            ->where('status_ii', self::STATUS_PENDING);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->where('status_i', self::STATUS_SUPPLIER_PAID)
                ->orWhere('status_ii', self::STATUS_CANTEEN_DEPOSITED);
        });
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForSupplier(Builder $query, int|string $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function isLocked(): bool
    {
        return $this->status_i === self::STATUS_SUPPLIER_PAID
            || $this->status_ii === self::STATUS_CANTEEN_DEPOSITED;
    }
}
