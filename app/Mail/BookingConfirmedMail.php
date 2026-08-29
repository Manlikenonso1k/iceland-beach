<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Sent when a stay is confirmed.
 *
 * Accepts either model because two paths confirm a booking:
 *   - BookingResource (Filament list actions) works on a Booking
 *   - BookingWorkflowService (PendingBookingsWidget) works on a Room
 * The two schemas name the same facts differently, so they are normalised to
 * plain scalars here and the view stays free of null-safe chains.
 */
class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    // NB: not $subject — Illuminate\Mail\Mailable already declares an
    // untyped $subject property (the email subject line), and a typed
    // promoted property of that name is a fatal redeclaration.
    public function __construct(public Room|Booking $record) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Booking Confirmed - Iceland Beach Resort');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-confirmed',
            with: $this->viewData(),
        );
    }

    /** @return array<string, mixed> */
    private function viewData(): array
    {
        $isBooking = $this->record instanceof Booking;

        $checkIn = $isBooking ? $this->record->check_in : $this->record->start_date;
        $checkOut = $isBooking ? $this->record->check_out : $this->record->end_date;

        $roomName = $isBooking
            ? ($this->record->room?->room_name ?: $this->record->room_type)
            : $this->record->room_name;

        return [
            // Kept so any existing override can still reach the model.
            'record' => $this->record,
            'guestName' => ($isBooking ? $this->record->guest_name : $this->record->customer_name) ?: 'Guest',
            'reference' => str_pad((string) $this->record->id, 6, '0', STR_PAD_LEFT),
            'roomName' => $roomName ?: 'Your room',
            'checkIn' => $checkIn ? Carbon::parse($checkIn) : null,
            'checkOut' => $checkOut ? Carbon::parse($checkOut) : null,
            'nights' => ($checkIn && $checkOut)
                ? Carbon::parse($checkIn)->startOfDay()->diffInDays(Carbon::parse($checkOut)->startOfDay())
                : null,
            'logoUrl' => asset('images/iceland-logo.png'),
            'siteUrl' => url('/'),
        ];
    }
}
