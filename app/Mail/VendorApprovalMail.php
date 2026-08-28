<?php

namespace App\Mail;

use App\Models\VendorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public VendorRequest $vendorRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Vendor Spot Request Has Been Approved — Iceland Beach Resort',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.vendor-approval',
            with: [
                'vendorRequest' => $this->vendorRequest,
                'spotName'      => $this->vendorRequest->vendorSpot->name,
                'price'         => number_format(50000),
            ],
        );
    }
}
