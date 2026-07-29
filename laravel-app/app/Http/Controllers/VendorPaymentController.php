<?php

namespace App\Http\Controllers;

use App\Mail\VendorApprovalMail;
use App\Models\VendorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VendorPaymentController extends Controller
{
    /**
     * Show the vendor spot payment page.
     * GET /vendor-payment/{vendorRequest}
     */
    public function show(VendorRequest $vendorRequest)
    {
        // Only approved requests can pay
        if ($vendorRequest->status !== 'approved') {
            if ($vendorRequest->status === 'paid') {
                return view('vendor.payment-success', [
                    'vendorRequest' => $vendorRequest,
                ]);
            }
            abort(404, 'This payment link is not valid.');
        }

        $vendorRequest->load('vendorSpot');

        return view('vendor.payment', [
            'vendorRequest' => $vendorRequest,
        ]);
    }

    /**
     * Initiate TGI Pay payment for a vendor spot.
     * POST /vendor-payment/{vendorRequest}/pay
     */
    public function initiatePay(Request $request, VendorRequest $vendorRequest)
    {
        if ($vendorRequest->status !== 'approved') {
            return redirect()->back()->with('error', 'This request is not eligible for payment.');
        }

        $vendorRequest->load('vendorSpot');

        $amountNaira = (float) $vendorRequest->vendorSpot->price; // 50000
        $orderRef    = 'VENDOR_' . $vendorRequest->id . '_' . uniqid();

        // Store the order ref on the vendor request for reconciliation
        $vendorRequest->update(['order_ref' => $orderRef]);

        $nameParts = explode(' ', trim($vendorRequest->name), 2);

        $payload = [
            'customerFirstName'    => $nameParts[0] ?? '',
            'customerLastName'     => $nameParts[1] ?? '',
            'customerEmail'        => $vendorRequest->email,
            'amount'               => $amountNaira,
            'transactionReference' => $orderRef,
            'currency'             => 'NGN',
        ];

        $response = Http::withHeaders([
            'integration-key' => config('services.tgipay.integration_key'),
        ])
            ->acceptJson()
            ->timeout(30)
            ->post(config('services.tgipay.base_url') . '/payment/initiate', $payload);

        if (!$response->ok()) {
            Log::error('TGI Pay vendor initiation failed', ['response' => $response->body()]);
            return redirect()->back()->with('error', 'Could not initiate payment. Please try again.');
        }

        Log::info('TGI Pay vendor initiation response', [
            'status' => $response->status(),
            'body'   => $response->body(),
            'json'   => $response->json(),
        ]);

        $paymentUrl = data_get($response->json(), 'body.data.url')
                   ?? data_get($response->json(), 'data.url')
                   ?? data_get($response->json(), 'url');

        if (!$paymentUrl) {
            Log::error('TGI Pay vendor: no payment URL found in response', ['json' => $response->json()]);
            return redirect()->back()->with('error', 'Gateway did not return a payment URL.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * TGI Pay redirects the customer here after vendor spot payment.
     * ANY /vendor-payment/callback?ref=...&status=...
     */
    public function callback(Request $request)
    {
        $ref    = (string) $request->input('ref', '');
        $status = (string) $request->input('status', 'processing');

        $internalStatus = match ($status) {
            'success', 'successful', 'completed', 'paid' => 'success',
            'failed', 'cancelled', 'canceled'             => 'failed',
            default                                        => 'processing',
        };

        if ($internalStatus !== 'success') {
            return redirect('/vendor-spots')->with('error', 'Payment was not completed.');
        }

        $vendorRequest = VendorRequest::where('order_ref', $ref)->first();

        if (!$vendorRequest) {
            Log::warning('Vendor TGI Pay callback: request not found', ['ref' => $ref]);
            return redirect('/vendor-spots')->with('error', 'Order not found.');
        }

        if ($vendorRequest->status !== 'paid') {
            $vendorRequest->update(['status' => 'paid']);
            $vendorRequest->vendorSpot->update(['status' => 'sold']);
        }

        return view('vendor.payment-success', [
            'vendorRequest' => $vendorRequest->load('vendorSpot'),
        ]);
    }

    /**
     * TGI Pay background webhook for vendor payments.
     * POST /webhooks/vendor-tgi
     */
    public function webhook(Request $request)
    {
        $ref    = (string) $request->input('ref', '');
        $status = (string) $request->input('status', 'processing');

        $internalStatus = match ($status) {
            'success', 'successful', 'completed', 'paid' => 'success',
            'failed', 'cancelled', 'canceled'             => 'failed',
            default                                        => 'processing',
        };

        if ($internalStatus !== 'success') {
            return response()->json(['message' => 'Status not successful, ignored'], 200);
        }

        $vendorRequest = VendorRequest::where('order_ref', $ref)->first();

        if (!$vendorRequest) {
            return response()->json(['message' => 'Vendor request not found'], 404);
        }

        if ($vendorRequest->status !== 'paid') {
            $vendorRequest->update(['status' => 'paid']);
            $vendorRequest->vendorSpot->update(['status' => 'sold']);
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }
}
