<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralTicketSale extends Model
{
    protected $fillable = [
        'referral_id',
        'ticket_id',
        'buyer_name',
        'buyer_email',
        'amount',
        'commission_amount',
        'commission_rate',
        'quantity',
        'status',
    ];

    protected $casts = [
        'amount' => 'integer',
        'commission_amount' => 'integer',
        'commission_rate' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
