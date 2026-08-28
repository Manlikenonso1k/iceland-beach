<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiningTable extends Model
{
    use SoftDeletes;

    protected $table = 'tables';

    protected $fillable = [
        'table_name',
        'assigned_waiter_id',
        'status',
    ];

    public function assignedWaiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class, 'assigned_waiter_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'table_id');
    }
}
