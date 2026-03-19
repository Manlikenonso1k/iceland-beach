<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_total_amount_and_total_in_words_from_items(): void
    {
        $invoice = Invoice::query()->create([
            'invoice_number' => '#19032601',
            'invoice_date' => now()->toDateString(),
            'customer_name' => 'Demo Customer',
            'customer_address' => 'Okun-Ajah, Lagos',
            'telephone' => '+2348028227526',
            'bank_name' => 'MONIEPOINT',
            'bank_account_number' => '5029208012',
            'bank_account_name' => 'NEW ICELAND BEACH RESORT',
        ]);

        $invoice->items()->createMany([
            [
                'description' => 'Adult Gate Fee',
                'quantity' => 3,
                'unit_price' => 5000,
                'sub_total' => 15000,
            ],
            [
                'description' => 'Stretcher Cabana',
                'quantity' => 1,
                'unit_price' => 25000,
                'sub_total' => 25000,
            ],
        ]);

        $invoice->syncCalculatedTotals();
        $invoice->refresh();

        $this->assertSame('40000.00', number_format((float) $invoice->total_amount, 2, '.', ''));
        $this->assertSame('Forty Thousand Naira Only', $invoice->total_in_words);
    }

    public function test_it_generates_invoice_number_with_hash_prefix(): void
    {
        $number = Invoice::generateInvoiceNumber();

        $this->assertMatchesRegularExpression('/^#\d{8}$/', $number);
    }
}
