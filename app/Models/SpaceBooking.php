<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A booking of a space (venue), the counterpart to Booking for rooms.
 *
 * Deliberately a near-copy of App\Models\Booking rather than a shared abstract
 * parent: the two availability rules are allowed to diverge later (spaces may
 * grow same-day multi-slot booking, rooms will not), and host.md asked for the
 * room flow to be reused without redesigning it.
 */
class SpaceBooking extends Model
{
    protected $table = 'space_bookings';

    /**
     * Statuses that actually hold a space. A booking only blocks availability
     * once staff have confirmed it — 'pending' requests never do, so guests can
     * keep requesting a space while earlier requests await review.
     *
     * Identical rule to Booking::BLOCKING_STATUSES.
     */
    public const BLOCKING_STATUSES = ['confirmed'];

    /**
     * The allowed status set. `status` is a plain string column (see the
     * migration), so this constant is the only place the vocabulary lives.
     */
    public const STATUSES = [
        'pending',
        'confirmed',
        'rejected',
        'pending_payment',
        'payment_failed',
    ];

    protected $fillable = [
        'space_id',
        'space_name',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guests',
        'email_opt_in',
        'check_in',
        'check_out',
        'status',
        'payment_reference',
        'payment_status',
        'rejection_reason',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'email_opt_in' => 'boolean',
        'guests' => 'integer',
    ];

    /**
     * The space catalogue row. `services` is the legacy table behind the
     * ServiceBooking model — the same naming quirk as `room` for Booking.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'space_id');
    }

    /**
     * Bookings that hold a space against the given date range.
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

    /** Nights spanned, floored at 1 so a same-day booking still bills once. */
    public function nights(): int
    {
        if (! $this->check_in || ! $this->check_out) {
            return 1;
        }

        return max(1, (int) $this->check_in->copy()->startOfDay()
            ->diffInDays($this->check_out->copy()->startOfDay()));
    }
}
