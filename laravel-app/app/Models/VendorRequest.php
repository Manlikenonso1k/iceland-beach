<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRequest extends Model
{
    protected $fillable = [
        'vendor_spot_id',
        'name',
        'email',
        'address',
        'expected_to_sell',
        'order_ref',
        'status',
    ];

    /**
     * A vendor request belongs to a vendor spot.
     */
    public function vendorSpot(): BelongsTo
    {
        return $this->belongsTo(VendorSpot::class);
    }
}
