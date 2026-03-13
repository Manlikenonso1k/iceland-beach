<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoidLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_item_id',
        'voided_by',
        'reason',
    ];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class, 'voided_by');
    }
}
