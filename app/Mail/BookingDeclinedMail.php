<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Room;
use App\Models\SpaceBooking;
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
    public function __construct(public Room|Booking|SpaceBooking $record, public ?string $reason = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->record instanceof SpaceBooking
                ? 'Space Booking Declined - Iceland Beach Resort'
                : 'Booking Declined - Iceland Beach Resort',
        );
    }

    public function content(): Content
    {
        $isSpace = $this->record instanceof SpaceBooking;
        $isBooking = $this->record instanceof Booking;
        $checkIn = ($isBooking || $isSpace) ? $this->record->check_in : $this->record->start_date;

        return new Content(
            view: 'mail.booking-declined',
            with: [
                'record' => $this->record,
                'guestName' => (($isBooking || $isSpace) ? $this->record->guest_name : $this->record->customer_name) ?: 'Guest',
                'roomName' => (match (true) {
                    $isSpace => $this->record->space?->service_name ?: $this->record->space_name,
                    $isBooking => $this->record->room?->room_name ?: $this->record->room_type,
                    default => $this->record->room_name,
                }) ?: ($isSpace ? 'Your space' : 'Your room'),
                'checkIn' => $checkIn ? Carbon::parse($checkIn) : null,
                'reason' => $this->reason,
                'logoUrl' => $this->inlineLogo(),
                'siteUrl' => url('/'),
            ],
        );
    }

    /**
     * Logo as an inline base64 data URI.
     *
     * A URL would point at APP_URL — on a local or private host that is
     * unreachable from Gmail/Outlook, so the logo renders as a broken image.
     * Embedding the bytes sidesteps hosting entirely.
     *
     * PNG/JPEG only: base64 SVG does not render in Outlook.
     */
    private function inlineLogo(): string
    {
        // Email-specific copy: 300px wide, 64-colour palette PNG. The full
        // logo base64s to ~87 KB, which pushed the message past Gmail's
        // ~102 KB clipping threshold; this one is ~18 KB.
        $path = public_path('images/iceland-logo-email.png');

        if (! is_file($path)) {
            $path = public_path('images/iceland-logo.png');
        }

        if (! is_file($path)) {
            return '';
        }

        $mime = @mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }
}
