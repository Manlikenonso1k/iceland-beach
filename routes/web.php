<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public invoice view for print-to-PDF (no DOMPDF needed)
Route::get('/invoices/{id}/view', function ($id) {
    $invoice = \App\Models\Invoice::with('items')->findOrFail($id);
    return view('pdf.invoice', ['invoice' => $invoice]);
})->name('invoices.view');

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// VENDOR SPOT BOOKING
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

Route::get('/vendor-spots', function () {
    return view('vendor-spots');
})->name('vendor-spots');

// Vendor Payment routes
Route::get('/vendor-payment/{vendorRequest}', [\App\Http\Controllers\VendorPaymentController::class, 'show'])->name('vendor.payment.show');
Route::post('/vendor-payment/{vendorRequest}/pay', [\App\Http\Controllers\VendorPaymentController::class, 'initiatePay'])->name('vendor.payment.initiate');
Route::any('/vendor-payment/callback', [\App\Http\Controllers\VendorPaymentController::class, 'callback'])->name('vendor.payment.callback')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/webhooks/vendor-tgi', [\App\Http\Controllers\VendorPaymentController::class, 'webhook'])->name('vendor.payment.webhook')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// TICKET PAYMENT ROUTES
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

// IMPORTANT: Specific sub-routes must be declared BEFORE the generic GET /ticket
// so Laravel's router matches them first (most-specific wins).

// QR scan / gate entry verify â€” this URL is embedded in the QR code
Route::get('/ticket/verify/{uuid}', [PaymentController::class, 'verifyEntry'])
    ->name('ticket.verify');

// Success page (server-side rendered, works offline after first load)
Route::get('/ticket/{uuid}/success', [PaymentController::class, 'success'])
    ->name('ticket.success');

// Ticket purchase form â€” replaces legacy ticket.php
Route::get('/ticket', [PaymentController::class, 'showTicketForm'])
    ->name('ticket.form');

// AJAX price calculator â€” replaces the inline PHP AJAX handler in legacy ticket.php
// CSRF excluded: pure JSON API called by JS fetch(), session not present in request
Route::post('/ticket/calculate-price', [PaymentController::class, 'calculatePrice'])
    ->name('ticket.calculate')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Paystack: frontend JS verifies reference then POSTs here
Route::post('/verify-payment', [PaymentController::class, 'paystackVerify']);

// TGIPay: Blade form with @csrf POSTs here â€” CSRF is now properly validated
Route::post('/initiate-tgipay', [PaymentController::class, 'initiateTgiPay']);

// TGIPay: customer is redirected back here (TGI Pay can use GET or POST)
Route::any('/tgipay/callback', [PaymentController::class, 'tgiPayCallback'])
    ->name('tgipay.callback')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// TGIPay: Background webhook notification
Route::post('/webhooks/tgi', [PaymentController::class, 'tgiPayWebhook'])
    ->name('tgipay.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// ── Paystack: ROOM BOOKING payments ──────────────────────────────────────
// Separate from the /ticket Paystack flow, which uses the inline popup and
// POSTs to /verify-payment. Do not merge the two.
//
// The callback carries no booking id — it resolves the booking from the
// `reference` query parameter Paystack appends, so the single callback URL
// registered in the Paystack dashboard works for every transaction.
Route::get('/booking/payment/callback', [\App\Http\Controllers\BookingPaymentController::class, 'callback'])
    ->name('booking.payment.callback');

// Paystack: SPACE BOOKING payments. Separate callback from the room flow, but
// the webhook is shared (Paystack allows one URL per mode) and dispatches on the
// "SPACE-" reference prefix inside PaystackWebhookController.
Route::get('/space-booking/payment/callback', [\App\Http\Controllers\SpaceBookingPaymentController::class, 'callback'])
    ->name('space.payment.callback');

// Post-payment success page and its PDF receipt.
//
// Both are `signed`. A booking id is a small sequential integer, so an unsigned
// /booking/receipt/5 would let anyone walk the range and harvest every guest's
// name, email and phone. Links are minted where the guest is already known to
// be legitimate: the Paystack callback redirect, and the confirmation email.
Route::get('/booking/success/{booking}', [\App\Http\Controllers\BookingReceiptController::class, 'success'])
    ->name('booking.success')
    ->middleware('signed');

Route::get('/booking/receipt/{booking}', [\App\Http\Controllers\BookingReceiptController::class, 'download'])
    ->name('booking.receipt')
    ->middleware('signed');

// Paystack webhook. Paystack accepts ONE URL per mode, and sends every event
// to it, so a single endpoint dispatches to tickets or bookings by reference
// prefix. The two aliases below point at the same handler so whichever URL is
// already registered in the dashboard keeps working.
Route::post('/webhooks/paystack', [\App\Http\Controllers\PaystackWebhookController::class, 'handle'])
    ->name('paystack.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/webhooks/paystack/booking', [\App\Http\Controllers\PaystackWebhookController::class, 'handle'])
    ->name('booking.payment.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/webhooks/paystack/tickets', [\App\Http\Controllers\PaystackWebhookController::class, 'handle'])
    ->name('ticket.payment.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// React public-site routes. Kept after all server-rendered ticket, payment, and vendor routes.
Route::view('/about', 'welcome');
Route::view('/rooms', 'welcome');
Route::view('/services', 'welcome');
Route::view('/event', 'welcome');
Route::view('/gallery', 'welcome');
Route::view('/membership', 'welcome');
Route::view('/contact', 'welcome');
Route::view('/book', 'welcome');