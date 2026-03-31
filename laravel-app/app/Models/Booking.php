<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'guest_name',
        'guest_email',
        'guest_phone',
        'room_type',
        'check_in',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'check_in' => 'datetime',
    ];

    // Helper: Map room names to abbreviations
    public static function abbreviateRoom($roomName)
    {
        $map = [
            'Premium 1C' => 'P1C',
            'Beach Apartment 1' => 'BA1',
            // Add more mappings as needed
        ];
        return $map[$roomName] ?? preg_replace('/\b(\w)/', '$1', preg_replace('/[^A-Za-z0-9 ]/', '', $roomName));
    }
}
