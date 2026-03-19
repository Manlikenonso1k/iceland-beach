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
