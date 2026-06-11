<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'ticket_type',
        'quantity',
        'amount',
        'order_ref',
        'payment_gateway',
        'payment_ref',
        'paid',
        'checked_in',
        'qr_code_path',
    ];

    protected $casts = [
        'paid'       => 'boolean',
        'checked_in' => 'boolean',
        'amount'     => 'integer',
        'quantity'   => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    /**
     * Amount in naira (for display only).
     */
    public function getAmountNairaAttribute(): string
    {
        return '₦' . number_format($this->amount / 100, 2);
    }
}
