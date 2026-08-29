<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralTicketSale extends Model
{
    protected $fillable = [
        'referral_id',
        'ticket_id',
        'amount',
        'quantity',
        'status',
    ];

    protected $casts = [
        'amount' => 'integer',
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
