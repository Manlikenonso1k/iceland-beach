<?php

namespace App\Services;

use App\Mail\BookingCustomerConfirmationMail;
use App\Mail\BookingNotificationMail;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class ReservationService
{
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

        $room->forceFill(['mailsent' => true])->save();
    }
}
