<?php

namespace App\Http\Controllers;

use App\Mail\TicketReceiptMail;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\SpaceBooking;
use App\Services\BookingWorkflowService;
use App\Services\PaystackService;
use App\Services\SpaceBookingWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Single Paystack webhook entry point for tickets, room bookings AND space bookings.
 *
 * Paystack accepts ONE webhook URL per mode (test / live) — there is no way to
 * register a separate endpoint per feature, and every event is sent to that one
 * URL. So this verifies the signature once and dispatches on the reference:
 * room bookings use a "BOOK-" prefix, space bookings "SPACE-", and tickets
 * anything else.
 *
 * Every branch is idempotent — the callback and the webhook routinely fire
 * for the same transaction, and neither may double-email or double-count.
 */
class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly BookingWorkflowService $workflow,
        private readonly SpaceBookingWorkflowService $spaceWorkflow,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('x-paystack-signature', '');

        if (! $this->paystack->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Rejected Paystack webhook with invalid signature');

            return response('Unauthorized', 401);
        }

        $event = json_decode($payload, true) ?: [];

        if (($event['event'] ?? '') !== 'charge.success') {
            // Acknowledge everything else so Paystack stops retrying.
            return response('OK', 200);
        }

        $reference = $event['data']['reference'] ?? null;

        if (! $reference) {
            return response('OK', 200);
        }

        try {
            match (true) {
                str_starts_with($reference, 'BOOK-') => $this->creditBooking($reference),
                str_starts_with($reference, 'SPACE-') => $this->creditSpaceBooking($reference),
                default => $this->creditTicket($reference),
            };
        } catch (\Throwable $e) {
            // Never 500 back to Paystack — it would retry indefinitely.
            Log::error('Paystack webhook processing failed: ' . $e->getMessage(), [
                'reference' => $reference,
            ]);
        }

        return response('OK', 200);
    }

    private function creditBooking(string $reference): void
    {
        // No payment_status filter here: the service already guards against a
        // second credit, and "!= 'paid'" silently skipped rows where the column
        // is still NULL, which is every booking taken before payment was
        // switched on.
        $booking = Booking::query()
            ->where('payment_reference', $reference)
            ->first();

        if (! $booking) {
            return;
        }

        // Same method BookingPaymentController uses, so whichever endpoint
        // Paystack happens to be pointed at produces the identical outcome:
        // confirmed booking, room held, guest emailed.
        $this->workflow->markBookingPaid($booking);
    }

    private function creditTicket(string $reference): void
    {
        $ticket = Ticket::query()
            ->where(fn ($q) => $q->where('order_ref', $reference)->orWhere('payment_ref', $reference))
            ->where('paid', false)
            ->first();

        if (! $ticket) {
            return;
        }

        $ticket->update([
            'paid' => true,
            'payment_ref' => $reference,
            'payment_gateway' => 'paystack',
        ]);

        // TicketObserver credits any referral automatically on the paid flip.

        try {
            Mail::to($ticket->email)->send(new TicketReceiptMail($ticket));
        } catch (\Throwable $e) {
            Log::error('Ticket receipt email failed after webhook: ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
            ]);
        }
    }
    /**
     * Space booking paid. Delegates to the same service the callback uses, so
     * the two cannot drift; markBookingPaid() is idempotent.
     */
    private function creditSpaceBooking(string $reference): void
    {
        $booking = SpaceBooking::query()->where('payment_reference', $reference)->first();

        if (! $booking) {
            Log::warning('Paystack webhook for unknown space booking reference', ['reference' => $reference]);

            return;
        }

        $this->spaceWorkflow->markBookingPaid($booking);
    }
}
