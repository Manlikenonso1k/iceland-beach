<?php

namespace App\Mail;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Room $room,
        public ?string $reason = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Declined - Iceland Beach Resort',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-declined',
            with: [
                'room' => $this->room,
                'reason' => $this->reason,
            ],
        );
    }
}
