<?php

namespace App\Observers;

use App\Models\Referral;
use App\Models\ReferralTicketSale;
use App\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Credits a referral when a ticket is actually paid for.
 *
 * A ticket can reach `paid = true` from four different places — Paystack
 * verification, the TGI callback, the TGI webhook, and the background payment
 * refresh service. Attributing the sale here instead of in each of those keeps
 * the payment code completely untouched and means no path can be forgotten.
 *
 * Failed or still-pending payments never reach the recording branch, and the
 * unique index on referral_ticket_sales.ticket_id makes a repeat callback a
 * no-op rather than a double credit.
 */
class TicketObserver
{
    public function saved(Ticket $ticket): void
    {
        if (! $ticket->paid) {
            return;
        }

        $referral = Referral::findByCode($ticket->referral_code);

        if (! $referral) {
            return;
        }

        // Cheap pre-check; the unique constraint below is the real guard.
        if (ReferralTicketSale::query()->where('ticket_id', $ticket->id)->exists()) {
            return;
        }

        $amount = (int) $ticket->amount;
        $rate = (float) $referral->commission_rate;

        try {
            ReferralTicketSale::create([
                'referral_id' => $referral->id,
                'ticket_id' => $ticket->id,
                'buyer_name' => $ticket->name ?: 'Guest',
                'buyer_email' => (string) $ticket->email,
                'amount' => $amount,
                // Snapshotted, not derived on read: the partner's rate may change
                // later and historical payouts must not move with it.
                'commission_rate' => $rate,
                'commission_amount' => (int) round($amount * $rate / 100),
                'quantity' => (int) ($ticket->quantity ?: 1),
                'status' => 'successful',
            ]);
        } catch (QueryException $exception) {
            // Concurrent callbacks racing the pre-check — the unique index did
            // its job, so treat it as already credited.
            Log::debug('Referral sale already recorded for ticket ' . $ticket->id);
        }
    }
}
