<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'waiter_id',
        'table_id',
        'total_amount',
        'payment_method',
        'sale_date',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'sale_date' => 'date',
        ];
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class);
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
