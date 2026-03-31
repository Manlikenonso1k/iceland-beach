<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public invoice view for print-to-PDF (no DOMPDF needed)
Route::get('/invoices/{id}/view', function ($id) {
    $invoice = \App\Models\Invoice::with('items')->findOrFail($id);
    return view('pdf.invoice', ['invoice' => $invoice]);
})->name('invoices.view');

// TEMPORARY: Reset password for user@example.com
Route::get('/reset-temp-password', function () {
    $user = \App\Models\User::where('email', 'victorynonso9@gmail.com')->first();
    if ($user) {
        $user->password = 'YXvvHRefb*e1@L8gR1AruDTK'; // Will be hashed by model cast
        $user->save();
        return 'Password reset successfully.';
    }
    return 'User not found.';
});
