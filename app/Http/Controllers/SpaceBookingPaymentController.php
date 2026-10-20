<?php

namespace App\Http\Controllers;

use App\Models\SpaceBooking;
use App\Services\PaystackService;
use App\Services\SpaceBookingWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Paystack return leg for SPACE bookings, mirroring BookingPaymentController.
 *
 * The webhook half lives in PaystackWebhookController, because Paystack allows
 * only one webhook URL per mode and every event goes to it; that controller
 * dispatches on the "SPACE-" reference prefix.
 *
 * Both entry points converge on SpaceBookingWorkflowService::markBookingPaid(),
 * which is idempotent — the callback and the webhook routinely both fire for one
 * transaction, and a guest must never be emailed twice.
 */
class SpaceBookingPaymentController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly SpaceBookingWorkflowService $workflow,
    ) {}

    /**
     * Guest returns from Paystack checkout.
     *
     * The booking is resolved from the `reference` query parameter Paystack
     * always appends rather than a route id, so the single callback URL
     * registered in the dashboard works for every booking.
     */
    public function callback(Request $request)
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));

        if ($reference === '') {
            return redirect('/services')->with('error', 'Missing payment reference.');
        }

        $booking = SpaceBooking::query()->where('payment_reference', $reference)->first();

        if (! $booking) {
            Log::warning('Paystack space callback for unknown reference', ['reference' => $reference]);

            return redirect('/services')->with('error', 'We could not find that booking.');
        }

        try {
            $data = $this->paystack->verify($reference);

            if (($data['status'] ?? '') === 'success') {
                $this->workflow->markBookingPaid($booking);

                return redirect('/services')->with(
                    'status',
                    'Payment received — your space booking is confirmed. A confirmation email is on its way.',
                );
            }

            $booking->update(['status' => 'payment_failed', 'payment_status' => 'failed']);

            return redirect('/services')->with('error', 'Payment was not completed. Please try again.');
        } catch (\Throwable $e) {
            Log::error('Paystack space callback error: ' . $e->getMessage(), [
                'space_booking_id' => $booking->id,
                'reference' => $reference,
            ]);

            return redirect('/services')->with('error', 'We could not verify that payment. Please contact us.');
        }
    }
}
