<?php

namespace App\Http\Controllers;

use App\Mail\TicketReceiptMail;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // PAYSTACK
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Verify a Paystack payment and create the ticket on success.
     * POST /verify-payment
     */
    public function paystackVerify(Request $request)
    {
        $ref = $request->input('paystack_ref');

        if (!$ref) {
            return redirect()->back()->with('error', 'No payment reference provided.');
        }

        // Verify server-side — never trust the frontend.
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->acceptJson()
            ->get(config('services.paystack.base_url') . '/transaction/verify/' . $ref);

        $body = $response->json();

        if (!$response->ok() || ($body['data']['status'] ?? '') !== 'success') {
            return redirect()->back()->with('error', 'Payment verification failed. Please contact support.');
        }

        $ticketType = $request->input('ticket_type');   // "Regular" or "VIP"
        $quantity   = (int) $request->input('quantity', 1);
        $unitPrice  = $ticketType === 'VIP' ? 10000 : 5000; // naira
        $amountKobo = $unitPrice * $quantity * 100;

        $ticket = Ticket::create([
            'name'             => $request->input('name'),
            'email'            => $request->input('email'),
            'phone'            => $request->input('phone'),
            'ticket_type'      => $ticketType,
            'quantity'         => $quantity,
            'amount'           => $amountKobo,
            'order_ref'        => uniqid('BEACH_'),
            'payment_gateway'  => 'paystack',
            'payment_ref'      => $ref,
            'paid'             => true,
        ]);

        $this->generateQr($ticket);
        $this->sendReceiptEmail($ticket);

        return redirect()->route('ticket.success', $ticket->uuid);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TGI TITAN (TGIPAY)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Initiate a Titan payment and redirect the customer to Titan's hosted page.
     * POST /initiate-titan
     *
     * Per PAYMENT_KNOWLEDGE.md: auth is via 'integration-key' header only — no HMAC.
     * Amount is sent as actual naira float (not kobo).
     */
    public function initiateTitan(Request $request)
    {
        $ticketType = $request->input('ticket_type');
        $quantity   = (int) $request->input('quantity', 1);
        $unitPrice  = $ticketType === 'VIP' ? 10000 : 5000; // naira
        $amountKobo = $unitPrice * $quantity * 100;
        $orderRef   = uniqid('BEACH_');

        // Save pending record BEFORE redirecting so we can reconcile on callback.
        $ticket = Ticket::create([
            'name'            => $request->input('name'),
            'email'           => $request->input('email'),
            'phone'           => $request->input('phone'),
            'ticket_type'     => $ticketType,
            'quantity'        => $quantity,
            'amount'          => $amountKobo,
            'order_ref'       => $orderRef,
            'payment_gateway' => 'titan',
            'paid'            => false,
        ]);

        $nameParts = explode(' ', trim($request->input('name')), 2);

        // Payload mirrors TgiPayGateway::initializePayment from PAYMENT_KNOWLEDGE.md.
        // TGIPAY expects amount in naira (float), not kobo.
        $payload = [
            'customerFirstName'  => $nameParts[0] ?? '',
            'customerLastName'   => $nameParts[1] ?? '',
            'customerEmail'      => $request->input('email'),
            'amount'             => (float) ($amountKobo / 100),  // back to naira for Titan
            'transactionReference' => $orderRef,
            'currency'           => 'NGN',
        ];

        $response = Http::withHeaders([
            'integration-key' => config('services.titan.api_key'),
        ])
            ->acceptJson()
            ->timeout(30)
            ->post(config('services.titan.base_url') . '/payment/initiate', $payload);

        if (!$response->ok()) {
            Log::error('Titan initiation failed', ['response' => $response->body()]);
            return redirect()->back()->with('error', 'Could not initiate payment. Please try again.');
        }

        $paymentUrl = data_get($response->json(), 'body.data.url');

        if (!$paymentUrl) {
            return redirect()->back()->with('error', 'Gateway did not return a payment URL.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * Titan redirects the customer here after payment.
     * GET /titan/callback?ref=...&status=...
     *
     * Per PAYMENT_KNOWLEDGE.md: Titan uses a GET redirect callback (not a POST webhook).
     * There is no HMAC signature on Titan; we query the API to confirm status.
     */
    public function titanCallback(Request $request)
    {
        $ref    = (string) $request->query('ref', '');
        $status = (string) $request->query('status', 'processing');

        // Normalise status exactly as in PAYMENT_KNOWLEDGE.md handleWebhook logic.
        $internalStatus = match ($status) {
            'success', 'successful', 'completed', 'paid' => 'success',
            'failed', 'cancelled', 'canceled'             => 'failed',
            default                                        => 'processing',
        };

        if ($internalStatus !== 'success') {
            return redirect('/')->with('error', 'Payment was not completed.');
        }

        $ticket = Ticket::where('order_ref', $ref)->first();

        if (!$ticket) {
            Log::warning('Titan callback: ticket not found', ['ref' => $ref]);
            return redirect('/')->with('error', 'Order not found.');
        }

        if (!$ticket->paid) {
            $ticket->update([
                'paid'        => true,
                'payment_ref' => $ref,
            ]);
            $this->generateQr($ticket);
            $this->sendReceiptEmail($ticket);
        }

        return redirect()->route('ticket.success', $ticket->uuid);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SUCCESS PAGE
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * GET /ticket/{uuid}/success
     */
    public function success(string $uuid)
    {
        $ticket = Ticket::where('uuid', $uuid)->firstOrFail();
        return view('ticket.success', compact('ticket'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // QR SCAN / ENTRY VERIFY
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * GET /ticket/verify/{uuid}
     * Called when admin scans QR at the gate.
     */
    public function verifyEntry(string $uuid)
    {
        $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

        if (!$ticket->paid) {
            return view('ticket.verify', ['ticket' => $ticket, 'status' => 'unpaid']);
        }

        if ($ticket->checked_in) {
            return view('ticket.verify', ['ticket' => $ticket, 'status' => 'already']);
        }

        $ticket->update(['checked_in' => true]);
        return view('ticket.verify', ['ticket' => $ticket, 'status' => 'ok']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generate SVG QR code and persist path on the ticket.
     * QR content = the verifyEntry route — admin scans this at the gate.
     * Saved to storage/app/public/qrcodes/{uuid}.svg so it survives restarts.
     */
    private function generateQr(Ticket $ticket): void
    {
        $content = route('ticket.verify', $ticket->uuid);
        $path    = "qrcodes/{$ticket->uuid}.svg";

        QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($content, storage_path("app/public/{$path}"));

        $ticket->update(['qr_code_path' => $path]);
    }

    /**
     * Send the receipt email with QR code embedded.
     */
    private function sendReceiptEmail(Ticket $ticket): void
    {
        Mail::to($ticket->email)->send(new TicketReceiptMail($ticket));
    }
}
