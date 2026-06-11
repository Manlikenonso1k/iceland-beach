<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Iceland Beach Event Ticket — ' . $this->ticket->order_ref,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ticket-receipt',
            with: ['ticket' => $this->ticket],
        );
    }
}
