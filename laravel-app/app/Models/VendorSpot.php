<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VendorSpot extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'price',
        'pos_x',
        'pos_y',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'pos_x' => 'decimal:4',
        'pos_y' => 'decimal:4',
    ];

    /**
     * A vendor spot has one vendor request.
     */
    public function vendorRequest(): HasOne
    {
        return $this->hasOne(VendorRequest::class);
    }
}
