<?php

namespace App\Services;

use App\Mail\BookingCustomerConfirmationMail;
use App\Mail\BookingNotificationMail;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ReservationService
{
    /**
     * Validates that no other booked room with the same name overlaps the given dates.
     * Throws a ValidationException on failure so Filament shows an inline form error.
     */
    public function assertNotOverlapping(?int $excludeId, string $roomName, Carbon $start, Carbon $end): void
    {
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages([
                'end_date' => 'Sign-out must be after sign-in.',
            ]);
        }

        $query = Room::query()
            ->where('room_name', $roomName)
            ->where('is_booked', 'booked')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<', $end)
            ->where('end_date', '>', $start);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'This room is already booked for the selected dates.',
            ]);
        }
    }

    /**
     * Public-site booking request.
     *
     * Creates a pending Booking (the canonical record) and stages the guest
     * details on the room WITHOUT setting is_booked = 'booked'. A request that
     * staff have not reviewed must never make the room look unavailable, and
     * leaving is_booked = 'no' with dates present is exactly what
     * PendingBookingsWidget already treats as awaiting approval.
     *
     * The room is only actually held once staff confirm — see
     * BookingWorkflowService.
     */
    public function requestBooking(
        Room $room,
        string $customerName,
        string $customerEmail,
        ?string $phone,
        ?int $guests,
        Carbon $start,
        Carbon $end,
        bool $emailOptIn = false,
        bool $sendEmails = true
    ): Booking
    {
        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Sign-out must be after sign-in.');
        }

        // Only confirmed bookings hold a room; pending ones are ignored here.
        $held = Booking::query()
            ->where('room_id', $room->id)
            ->blocking($start, $end)
            ->exists();

        if ($held || $this->roomHeldOnRoomRecord($room, $start, $end)) {
            throw new InvalidArgumentException('This room is already booked for the selected dates.');
        }

        $booking = Booking::create([
            'room_id' => $room->id,
            'guest_name' => $customerName,
            'guest_email' => $customerEmail,
            'guest_phone' => $phone ?? '',
            'email_opt_in' => $emailOptIn,
            'room_type' => $room->room_name,
            'check_in' => $start,
            'check_out' => $end,
            'status' => 'pending',
        ]);

        // Stage guest details for the confirmation emails and the pending queue,
        // but deliberately leave is_booked alone.
        $room->fill([
            'customer_name' => $customerName,
            'email' => $customerEmail,
            'start_date' => $start,
            'end_date' => $end,
            'mailsent' => false,
        ]);
	$room->timestamps = false;
        $room->save();

        if ($sendEmails) {
            $this->sendBookingEmails($room, $phone, $guests);
        }

        return $booking;
    }

    /**
     * Legacy availability check against the room record itself, kept so rooms
     * held before bookings became canonical still block correctly.
     */
    private function roomHeldOnRoomRecord(Room $room, Carbon $start, Carbon $end): bool
    {
        return Room::query()
            ->where('id', '!=', $room->id)
            ->where('room_name', $room->room_name)
            ->where('is_booked', 'booked')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<', $end)
            ->where('end_date', '>', $start)
            ->exists();
    }

    public function reserveRoom(
        Room $room,
        string $customerName,
        string $customerEmail,
        ?string $phone,
        ?int $guests,
        Carbon $start,
        Carbon $end,
        bool $sendEmails = true
    ): Room
    {
        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Sign-out must be after sign-in.');
        }

        $hasOverlap = Room::query()
            ->where('id', '!=', $room->id)
            ->where('room_name', $room->room_name)
            ->where('is_booked', 'booked')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<', $end)
            ->where('end_date', '>', $start)
            ->exists();

        if ($hasOverlap) {
            throw new InvalidArgumentException('This room is already booked for the selected dates.');
        }

        $room->fill([
            'is_booked' => 'booked',
            'customer_name' => $customerName,
            'email' => $customerEmail,
            'start_date' => $start,
            'end_date' => $end,
            'mailsent' => false,
        ]);
        $room->save();

        if ($sendEmails) {
            $this->sendBookingEmails($room, $phone, $guests);
        }

        return $room->refresh();
    }

    public function sendBookingEmails(Room $room, ?string $phone = null, ?int $guests = null): void
    {
        $adminRecipients = config('resort.booking_admin_recipients', [
            'booking@icelandbeach.com',
            'v.chinonso@collegeofartslagos.com',
            'akapo@icelandbeach.com',
            'info@icelandbeach.com',
        ]);

        Mail::to($adminRecipients)->send(new BookingNotificationMail($room, $phone, $guests));
        if (! empty($room->email)) {
            Mail::to($room->email)->send(new BookingCustomerConfirmationMail($room, $phone, $guests));
        }
	$room->timestamps = false;
        $room->forceFill(['mailsent' => true])->save();
    }
}
