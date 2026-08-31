<?php

namespace App\Http\Controllers;

use App\Mail\TicketReceiptMail;
use App\Models\Booking;
use App\Models\Ticket;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Single Paystack webhook entry point for BOTH tickets and room bookings.
 *
 * Paystack accepts ONE webhook URL per mode (test / live) — there is no way to
 * register a separate endpoint per feature, and every event is sent to that one
 * URL. So this verifies the signature once and dispatches on the reference:
 * room bookings use a "BOOK-" prefix, tickets use anything else.
 *
 * Both branches are idempotent — the callback and the webhook routinely fire
 * for the same transaction, and neither may double-email or double-count.
 */
class PaystackWebhookController extends Controller
{
    public function __construct(private readonly PaystackService $paystack) {}

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
            str_starts_with($reference, 'BOOK-')
                ? $this->creditBooking($reference)
                : $this->creditTicket($reference);
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
        $booking = Booking::query()
            ->where('payment_reference', $reference)
            ->where('payment_status', '!=', 'paid')
            ->first();

        if (! $booking) {
            return;
        }

        // 'pending', not 'confirmed' — payment does not bypass staff review.
        $booking->update(['status' => 'pending', 'payment_status' => 'paid']);
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
}
