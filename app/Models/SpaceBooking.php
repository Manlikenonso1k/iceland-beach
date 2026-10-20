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
        'source',
        'receipt_path',
        'receipt_original_name',
        'receipt_mime',
        'receipt_uploaded_at',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'receipt_uploaded_at' => 'datetime',
        'email_opt_in' => 'boolean',
        'guests' => 'integer',
    ];

    /** Taken in the backend by staff — money changed hands off-system. */
    public const SOURCE_MANUAL = 'manual';

    /** Came from the public website, where Paystack is already the receipt. */
    public const SOURCE_WEBSITE = 'website';

    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }

    public function hasReceipt(): bool
    {
        return filled($this->receipt_path);
    }

    /**
     * Whether this booking is allowed to reach 'confirmed'.
     *
     * A manual booking needs receipt evidence first. A paid booking does not:
     * Paystack has already recorded the money, so requiring a second document
     * would block the automatic confirmation that payment triggers.
     */
    public function requiresReceiptBeforeConfirming(): bool
    {
        return $this->isManual()
            && $this->payment_status !== 'paid'
            && ! $this->hasReceipt();
    }

    /**
     * Permanent signed URL to the receipt, or null when there is none.
     *
     * Signed rather than login-protected because these links are embedded in
     * exported report PDFs that may be opened on a device with no panel
     * session. No expiry: an archived report read months later must still
     * resolve. See SpaceReceiptController.
     */
    public function receiptUrl(): ?string
    {
        if (! $this->hasReceipt()) {
            return null;
        }

        return \Illuminate\Support\Facades\URL::signedRoute('space.receipt', ['spaceBooking' => $this->id]);
    }

    /**
     * What the Receipt column shows. Website bookings are exempt from the
     * requirement — Paystack is already the receipt there.
     */
    public function receiptLabel(): string
    {
        if (! $this->isManual()) {
            return 'Online';
        }

        return $this->hasReceipt() ? 'View receipt' : '—';
    }

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
