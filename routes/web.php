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

// React public-site routes. Kept after all server-rendered ticket, payment, and vendor routes.
Route::view('/about', 'welcome');
Route::view('/rooms', 'welcome');
Route::view('/services', 'welcome');
Route::view('/event', 'welcome');
Route::view('/gallery', 'welcome');
Route::view('/membership', 'welcome');
Route::view('/contact', 'welcome');
Route::view('/book', 'welcome');