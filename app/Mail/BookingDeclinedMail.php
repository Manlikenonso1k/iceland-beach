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
 * Sent when a booking request is declined.
 *
 * Accepts either model for the same reason as BookingConfirmedMail — the
 * Filament resource rejects a Booking, BookingWorkflowService rejects a Room.
 */
class BookingDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    // NB: not $subject — Illuminate\Mail\Mailable already declares an
    // untyped $subject property (the email subject line), and a typed
    // promoted property of that name is a fatal redeclaration.
    public function __construct(public Room|Booking $record, public ?string $reason = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Booking Declined - Iceland Beach Resort');
    }

    public function content(): Content
    {
        $isBooking = $this->record instanceof Booking;
        $checkIn = $isBooking ? $this->record->check_in : $this->record->start_date;

        return new Content(
            view: 'mail.booking-declined',
            with: [
                'record' => $this->record,
                'guestName' => ($isBooking ? $this->record->guest_name : $this->record->customer_name) ?: 'Guest',
                'roomName' => ($isBooking
                    ? ($this->record->room?->room_name ?: $this->record->room_type)
                    : $this->record->room_name) ?: 'Your room',
                'checkIn' => $checkIn ? Carbon::parse($checkIn) : null,
                'reason' => $this->reason,
                'logoUrl' => asset('images/iceland-logo.png'),
                'siteUrl' => url('/'),
            ],
        );
    }
}
