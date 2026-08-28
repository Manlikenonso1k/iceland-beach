<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceBooking extends Model
{
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'service_name',
        'service_category',
        'service_price',
        'service_image',
        'is_booked',
        'customers_name',
        'customers_email',
        'signin',
        'signout',
        'no_of_people',
        'mailsent',
    ];

    protected function casts(): array
    {
        return [
            'service_price' => 'decimal:2',
            'signin' => 'datetime',
            'signout' => 'datetime',
            'mailsent' => 'boolean',
        ];
    }
}
