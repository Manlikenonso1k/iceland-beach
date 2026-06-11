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

// ──────────────────────────────────────────────────────────────────────────────
// TICKET PAYMENT ROUTES
// ──────────────────────────────────────────────────────────────────────────────

// Paystack: frontend verifies reference then POSTs here
Route::post('/verify-payment', [PaymentController::class, 'paystackVerify']);

// Titan: form submits here, we initiate and redirect to Titan hosted page
Route::post('/initiate-titan', [PaymentController::class, 'initiateTitan']);

// Titan: customer redirected back here after payment (GET, not a POST webhook)
// CSRF excluded via withoutMiddleware — per PAYMENT_KNOWLEDGE.md gotchas
Route::get('/titan/callback', [PaymentController::class, 'titanCallback'])
    ->name('titan.callback')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Success page (server-side rendered, works offline after first load)
Route::get('/ticket/{uuid}/success', [PaymentController::class, 'success'])
    ->name('ticket.success');

// QR scan / gate entry verify — this URL is embedded in the QR code
Route::get('/ticket/verify/{uuid}', [PaymentController::class, 'verifyEntry'])
    ->name('ticket.verify');
