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
use Illuminate\Support\Facades\URL;

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
            'logoUrl' => $this->inlineLogo(),
            'siteUrl' => url('/'),
            'receiptUrl' => $this->receiptUrl(),
        ];
    }

    /**
     * Signed link to the PDF receipt, or null when there is nothing to receipt.
     *
     * Only a paid Booking gets one: a staff-confirmed request has taken no
     * money, and a Room-shaped record has no payment to point at. Signed
     * because the receipt carries the guest's own details and the route is
     * keyed by a guessable sequential id.
     */
    private function receiptUrl(): ?string
    {
        if (! $this->record instanceof Booking || $this->record->payment_status !== 'paid') {
            return null;
        }

        return URL::signedRoute('booking.receipt', ['booking' => $this->record->id]);
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
