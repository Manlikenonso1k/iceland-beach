<?php

namespace App\Http\Controllers;

use App\Mail\BookingCustomerConfirmationMail;
use App\Models\Booking;
use App\Services\PaystackService;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Paystack payment handling for ROOM BOOKINGS only.
 *
 * The /ticket page keeps its own inline-popup flow untouched.
 *
 * Both entry points converge on markPaid(), which is idempotent — the callback
 * and the webhook routinely both fire for the same transaction, and a guest
 * must never be emailed twice or a payment counted twice.
 */
class BookingPaymentController extends Controller
{
    public function __construct(private readonly PaystackService $paystack) {}

    /**
     * Guest returns from Paystack checkout.
     *
     * The booking is resolved from the `reference` query parameter that
     * Paystack always appends, rather than a route id. That way the single
     * callback URL registered in the Paystack dashboard works for every
     * booking without needing per-transaction paths.
     */
    public function callback(Request $request)
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));

        if ($reference === '') {
            return redirect('/rooms')->with('error', 'Missing payment reference.');
        }

        $booking = Booking::query()->where('payment_reference', $reference)->first();

        if (! $booking) {
            Log::warning('Paystack callback for unknown reference', ['reference' => $reference]);

            return redirect('/rooms')->with('error', 'We could not find that booking.');
        }

        try {
            $data = $this->paystack->verify($reference);

            if (($data['status'] ?? '') === 'success') {
                $this->markPaid($booking);

                return redirect('/')->with(
                    'success',
                    'Payment received. Your booking request has been sent to our team.',
                );
            }

            $booking->update(['status' => 'payment_failed', 'payment_status' => 'failed']);

            return redirect('/rooms')->with('error', 'Payment was not completed. Please try again.');
        } catch (\Throwable $e) {
            Log::error('Paystack callback error: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'reference' => $reference,
            ]);

            return redirect('/rooms')->with('error', 'We could not verify that payment. Please contact us.');
        }
    }

    /**
     * Server-to-server notification — the safety net for when the guest closes
     * the browser before being redirected back.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('x-paystack-signature', '');

        if (! $this->paystack->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Rejected Paystack webhook with invalid signature');

            return response('Unauthorized', 401);
        }

        $event = json_decode($payload, true) ?: [];

        if (($event['event'] ?? '') === 'charge.success') {
            $reference = $event['data']['reference'] ?? null;

            if ($reference) {
                $booking = Booking::query()->where('payment_reference', $reference)->first();

                if ($booking) {
                    $this->markPaid($booking);
                }
            }
        }

        // Always 200 on a validly-signed request, or Paystack keeps retrying.
        return response('OK', 200);
    }

    /**
     * Move a booking from awaiting-payment into the staff review queue.
     *
     * Idempotent by design: returns early if already paid, so a callback and a
     * webhook arriving for the same transaction cannot double-send the email.
     */
    private function markPaid(Booking $booking): void
    {
        if ($booking->payment_status === 'paid') {
            return;
        }

        $booking->update([
            // 'pending', not 'confirmed' — payment does not skip staff review,
            // and it must never hold the room on its own.
            'status' => 'pending',
            'payment_status' => 'paid',
        ]);

        if (blank($booking->guest_email)) {
            return;
        }

        try {
            $room = $booking->room;

            if ($room) {
                // Reuse the existing request-received mailable so paid and
                // unpaid bookings send the guest the same message.
                $preview = $room->replicate();
                $preview->id = $room->id;
                $preview->customer_name = $booking->guest_name;
                $preview->email = $booking->guest_email;
                $preview->start_date = $booking->check_in;
                $preview->end_date = $booking->check_out;

                Mail::to($booking->guest_email)->send(
                    new BookingCustomerConfirmationMail($preview, $booking->guest_phone, null),
                );
            }
        } catch (\Throwable $e) {
            // A mail failure must not undo a recorded payment.
            Log::error('Booking email failed after payment: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
            ]);
        }
    }
}
