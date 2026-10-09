<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant isolation is inherited through the parent order (order_id → orders,
 * which is tenant-scoped), so this table needs no scope of its own.
 */
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'meal_id',
        'name_snapshot',
        'unit_price',
        'quantity',
        'prep_time_minutes_snapshot',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'prep_time_minutes_snapshot' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }
}
