<?php

namespace App\Services;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingDeclinedMail;
use App\Models\SpaceBooking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Every space-booking status change goes through here, exactly as every room
 * status change goes through BookingWorkflowService.
 *
 * The room version exists because the Filament action, the dashboard widget and
 * the two payment entry points each carried their own copy of the logic and had
 * already drifted — one emailed the guest, the other did not. This service is
 * the same guarantee for spaces: status, the `services.is_booked` mirror, the
 * activity log and the guest email always move together.
 */
class SpaceBookingWorkflowService
{
    /**
     * Confirm a space booking and hold the space.
     *
     * Also mirrors the hold onto `services.is_booked`, because the legacy
     * public catalogue and ServiceBooking-based screens still read it — the
     * same denormalised mirror Booking keeps on `room.is_booked`.
     */
    public function confirmBooking(SpaceBooking $booking): void
    {
        // Enforced HERE and not only in the form, so the Filament action, the
        // pending widget and any future caller are all covered by one rule.
        // A manual booking took money off-system, and the receipt is the only
        // record that it happened. Paid bookings are exempt — Paystack already
        // is the receipt, and requiring a second document would block the
        // automatic confirmation that payment triggers.
        if ($booking->requiresReceiptBeforeConfirming()) {
            throw ValidationException::withMessages([
                'receipt' => 'Upload a receipt before confirming a manual space booking.',
            ]);
        }

        $previousStatus = $booking->status;

        $booking->update([
            'status' => 'confirmed',
            'rejection_reason' => null,
        ]);

        $this->mirrorToCatalogue($booking, 'booked');

        ActivityLogService::log(
            event: 'space_booking_confirmed',
            description: 'Space booking confirmed',
            subject: $booking,
            properties: [
                'space_name' => $booking->space_name,
                'previous_status' => $previousStatus,
                'new_status' => 'confirmed',
                'customer_email' => $booking->guest_email,
                'actor_email' => Auth::user()?->email,
            ],
        );

        // guest_email is nullable — staff take space bookings by phone too.
        if (! empty($booking->guest_email)) {
            Mail::to($booking->guest_email)->send(new BookingConfirmedMail($booking));
        }
    }

    /** Reject a space booking and release the space again. */
    public function rejectBooking(SpaceBooking $booking, ?string $reason = null): void
    {
        $cleanReason = trim((string) $reason);
        $previousStatus = $booking->status;

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        // A rejection must never leave the space held.
        $this->mirrorToCatalogue($booking, 'no');

        ActivityLogService::log(
            event: 'space_booking_rejected',
            description: 'Space booking rejected',
            subject: $booking,
            properties: [
                'space_name' => $booking->space_name,
                'previous_status' => $previousStatus,
                'new_status' => 'rejected',
                'rejection_reason' => $booking->rejection_reason,
                'customer_email' => $booking->guest_email,
                'actor_email' => Auth::user()?->email,
            ],
        );

        if (! empty($booking->guest_email)) {
            Mail::to($booking->guest_email)->send(new BookingDeclinedMail($booking, $booking->rejection_reason));
        }
    }

    /**
     * Record a successful Paystack payment for a space booking.
     *
     * Paying auto-approves, through the very same path staff use, so a paid
     * space booking is indistinguishable from a hand-confirmed one.
     *
     * Idempotent. The Paystack callback and the webhook routinely both fire for
     * one transaction and the guest must never be emailed twice — identical
     * reasoning to BookingWorkflowService::markBookingPaid().
     */
    public function markBookingPaid(SpaceBooking $booking): void
    {
        if ($booking->payment_status === 'paid') {
            return;
        }

        // Latch the payment before anything that can fail. Money Paystack has
        // already taken must survive a bad address or an unreachable mail host.
        $booking->update(['payment_status' => 'paid']);

        try {
            $this->confirmBooking($booking);
        } catch (\Throwable $e) {
            // Deliberately swallowed: re-throwing would show the returning
            // guest a "could not verify payment" page for money that was in
            // fact taken, and would make Paystack retry the webhook forever.
            Log::error('Confirming space booking after payment failed: ' . $e->getMessage(), [
                'space_booking_id' => $booking->id,
            ]);
        }
    }

    /**
     * Keep the legacy `services` row in step with the ledger.
     *
     * Timestamps are disabled: mirroring a hold is not a catalogue edit, and
     * touching updated_at here would reorder the public catalogue listing.
     */
    private function mirrorToCatalogue(SpaceBooking $booking, string $state): void
    {
        $space = $booking->space;

        if (! $space) {
            return;
        }

        $space->timestamps = false;

        $space->forceFill($state === 'booked' ? [
            'is_booked' => 'booked',
            'customers_name' => $booking->guest_name,
            'customers_email' => $booking->guest_email,
            'signin' => $booking->check_in,
            'signout' => $booking->check_out,
            'no_of_people' => $booking->guests,
        ] : [
            'is_booked' => 'no',
        ])->save();
    }
}
