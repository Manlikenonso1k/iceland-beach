<?php

namespace App\Models;

use App\Support\NairaAmountFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'customer_name',
        'customer_address',
        'telephone',
        'total_amount',
        'total_in_words',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }

            if (empty($invoice->bank_name)) {
                $invoice->bank_name = (string) config('resort.invoice.bank_name', 'MONIEPOINT');
            }

            if (empty($invoice->bank_account_number)) {
                $invoice->bank_account_number = (string) config('resort.invoice.bank_account_number', '5029208012');
            }

            if (empty($invoice->bank_account_name)) {
                $invoice->bank_account_name = (string) config('resort.invoice.bank_account_name', 'NEW ICELAND BEACH RESORT');
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = now()->format('dmy');
        $counter = 1;

        do {
            $candidate = '#' . $prefix . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $exists = static::query()->where('invoice_number', $candidate)->exists();
            $counter++;
        } while ($exists);

        return $candidate;
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function syncCalculatedTotals(): void
    {
        $total = (float) $this->items()->sum('sub_total');

        $this->forceFill([
            'total_amount' => $total,
            'total_in_words' => NairaAmountFormatter::toWords($total),
        ])->saveQuietly();
    }
}
