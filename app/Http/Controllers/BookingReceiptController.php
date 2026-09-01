<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Post-payment success page and downloadable receipt.
 *
 * Both routes carry Laravel's `signed` middleware. A booking id is a small
 * sequential integer, so an unsigned /booking/receipt/5 would let anyone walk
 * the range and harvest every guest's name, email and phone. The signature is
 * generated at the point we already know the guest is legitimate — the Paystack
 * callback, and the confirmation email — and is not guessable without APP_KEY.
 */
class BookingReceiptController extends Controller
{
    public function success(Booking $booking): Response
    {
        $this->assertPaid($booking);

        $booking->load('room');

        return response()->view('booking.success', [
            'booking' => $booking,
            'reference' => $this->reference($booking),
            'nights' => $this->nights($booking),
            'total' => $this->total($booking),
        ]);
    }

    public function download(Booking $booking): Response
    {
        $this->assertPaid($booking);

        $booking->load('room');

        $pdf = Pdf::loadView('booking.receipt-pdf', [
            'booking' => $booking,
            'reference' => $this->reference($booking),
            'nights' => $this->nights($booking),
            'total' => $this->total($booking),
            'logoBase64' => $this->inlineLogo(),
        ])
            // The document is set in DejaVu Sans purely because it is the only
            // bundled family carrying the naira sign, matching the invoice PDF.
            // Without subsetting DomPDF embeds that whole font — most of a
            // megabyte for a one-page receipt a guest opens on mobile data.
            // Scoped here rather than published to config/dompdf.php so the
            // existing invoice output is untouched.
            ->setOption('enable_font_subsetting', true)
            ->setPaper('a4', 'portrait');

        return $pdf->download('iceland-beach-receipt-' . $this->reference($booking) . '.pdf');
    }

    /**
     * A receipt only exists for money actually taken. Anything else — a pending
     * request, a failed charge — has nothing to receipt, so it 404s rather than
     * rendering a document that implies payment.
     */
    private function assertPaid(Booking $booking): void
    {
        abort_unless($booking->payment_status === 'paid', 404);
    }

    private function reference(Booking $booking): string
    {
        return str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
    }

    private function nights(Booking $booking): int
    {
        if (! $booking->check_in || ! $booking->check_out) {
            return 1;
        }

        return max(1, (int) Carbon::parse($booking->check_in)->startOfDay()
            ->diffInDays(Carbon::parse($booking->check_out)->startOfDay()));
    }

    /**
     * Recomputed from the room's nightly rate, which is what
     * Api\BookingController charged Paystack. bookings has no amount column, so
     * a rate changed after the stay was booked would restate this figure — see
     * the note on the receipt view.
     */
    private function total(Booking $booking): float
    {
        return (float) ($booking->room->room_price ?? 0) * $this->nights($booking);
    }

    /**
     * Logo as a base64 data URI. DomPDF fetches remote images over HTTP, which
     * on this host is either blocked or slow enough to stall the render, so the
     * bytes are embedded instead.
     */
    private function inlineLogo(): string
    {
        // The email-optimised copy first: 14 KB against the full logo's 67 KB,
        // and at 42px tall in the header the difference is invisible. Guests
        // download this over mobile data, so the bytes matter.
        $path = public_path('images/iceland-logo-email.png');

        if (! is_file($path)) {
            $path = public_path('images/iceland-logo.png');
        }

        if (! is_file($path)) {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode((string) file_get_contents($path));
    }
}
