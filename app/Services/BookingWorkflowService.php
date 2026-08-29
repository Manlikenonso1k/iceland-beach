<?php

namespace App\Services;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingDeclinedMail;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class BookingWorkflowService
{
    /**
     * Confirm a Booking (the canonical reservation record).
     *
     * Also mirrors the hold onto the room, because the calendar widget and the
     * legacy availability check both read room.is_booked. Both the Filament
     * resource action and PendingBookingsWidget call this so the behaviour
     * cannot drift between them.
     */
    public function confirmBooking(Booking $booking): void
    {
        $previousStatus = $booking->status;

        $booking->update([
            'status' => 'confirmed',
            'rejection_reason' => null,
        ]);

        $booking->room?->forceFill([
            'is_booked' => 'booked',
            'customer_name' => $booking->guest_name,
            'email' => $booking->guest_email,
            'start_date' => $booking->check_in,
            'end_date' => $booking->check_out,
        ])->save();

        ActivityLogService::log(
            event: 'booking_confirmed',
            description: 'Booking confirmed',
            subject: $booking,
            properties: [
                'room_name' => $booking->room_type,
                'previous_status' => $previousStatus,
                'new_status' => 'confirmed',
                'customer_email' => $booking->guest_email,
                'actor_email' => Auth::user()?->email,
            ],
        );

        if (! empty($booking->guest_email)) {
            Mail::to($booking->guest_email)->send(new BookingConfirmedMail($booking));
        }
    }

    /** Reject a Booking and release the room again. */
    public function rejectBooking(Booking $booking, ?string $reason = null): void
    {
        $cleanReason = trim((string) $reason);
        $previousStatus = $booking->status;

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        // A rejection must never leave the room held.
        $booking->room?->forceFill(['is_booked' => 'no'])->save();

        ActivityLogService::log(
            event: 'booking_rejected',
            description: 'Booking rejected',
            subject: $booking,
            properties: [
                'room_name' => $booking->room_type,
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

    public function confirm(Room $room): void
    {
        $previousStatus = $room->is_booked;

        $room->update([
            'is_booked' => 'booked',
            'rejection_reason' => null,
        ]);

        ActivityLogService::log(
            event: 'room_confirmed',
            description: 'Room booking confirmed',
            subject: $room,
            properties: [
                'room_name' => $room->room_name,
                'previous_status' => $previousStatus,
                'new_status' => 'booked',
                'customer_email' => $room->email,
                'actor_email' => Auth::user()?->email,
            ],
        );

        if (! empty($room->email)) {
            Mail::to($room->email)->send(new BookingConfirmedMail($room));
            $room->forceFill(['mailsent' => true])->save();
        }
    }

    public function reject(Room $room, ?string $reason = null): void
    {
        $cleanReason = trim((string) $reason);
        $previousStatus = $room->is_booked;

        $room->update([
            'is_booked' => 'rejected',
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        ActivityLogService::log(
            event: 'room_rejected',
            description: 'Room booking rejected',
            subject: $room,
            properties: [
                'room_name' => $room->room_name,
                'previous_status' => $previousStatus,
                'new_status' => 'rejected',
                'rejection_reason' => $room->rejection_reason,
                'customer_email' => $room->email,
                'actor_email' => Auth::user()?->email,
            ],
        );

        if (! empty($room->email)) {
            Mail::to($room->email)->send(new BookingDeclinedMail($room, $room->rejection_reason));
            $room->forceFill(['mailsent' => true])->save();
        }
    }
}
