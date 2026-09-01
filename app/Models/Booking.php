<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    protected $table = 'bookings';

    /**
     * Statuses that actually hold a room. A booking only blocks availability
     * once staff have confirmed it — 'pending' requests never do, so guests can
     * keep booking a room while earlier requests await review.
     */
    public const BLOCKING_STATUSES = ['confirmed'];

    protected $fillable = [
        'room_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'email_opt_in',
        'room_type',
        'check_in',
        'check_out',
        'status',
        // Without these two the Paystack flow silently breaks: update() drops
        // any non-fillable key, so the reference generated in
        // Api\BookingController::store() was never persisted and the callback
        // could never find the booking again.
        'payment_reference',
        'payment_status',
        'rejection_reason',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'email_opt_in' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Bookings that hold a room against the given date range.
     *
     * Overlap is half-open — a booking ending exactly when another begins does
     * not conflict, so same-day turnover stays bookable.
     */
    public function scopeBlocking(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in', '<', $end)
            ->where(function (Builder $q) use ($start): void {
                $q->where('check_out', '>', $start)
                    ->orWhereNull('check_out');
            });
    }

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
