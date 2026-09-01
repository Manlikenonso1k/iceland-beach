<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingWorkflowService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

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
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly BookingWorkflowService $workflow,
    ) {}

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

                // Straight to the branded receipt page rather than the
                // homepage, so the guest lands on proof of what they just paid
                // for. Signed because the page carries their own details.
                return redirect()->to(URL::signedRoute('booking.success', ['booking' => $booking->id]));
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
     * Record the payment and confirm the booking.
     *
     * Thin delegate on purpose. The identical transition is reachable from
     * PaystackWebhookController (Paystack allows only one webhook URL per mode,
     * so the shared endpoint dispatches there too), and the two used to carry
     * separate copies of this logic that had already drifted — one emailed the
     * guest, the other did not. One method, one behaviour.
     */
    private function markPaid(Booking $booking): void
    {
        $this->workflow->markBookingPaid($booking);
    }
}
