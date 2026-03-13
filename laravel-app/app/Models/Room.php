<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $table = 'room';

    protected $fillable = [
        'room_name',
        'room_category',
        'room_price',
        'room_image',
        'is_booked',
        'customer_name',
        'email',
        'start_date',
        'end_date',
        'total_price',
        'mailsent',
    ];

    protected function casts(): array
    {
        return [
            'room_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'mailsent' => 'boolean',
        ];
    }
}
