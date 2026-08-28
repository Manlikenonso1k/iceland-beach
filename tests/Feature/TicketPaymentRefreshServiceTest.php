<?php

namespace Tests\Feature;

use App\Mail\TicketReceiptMail;
use App\Models\Ticket;
use App\Services\TicketPaymentRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TicketPaymentRefreshServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_confirms_tgi_pay_payment_generates_qr_and_sends_receipt(): void
    {
        Mail::fake();

        Http::fake([
            rtrim((string) config('services.tgipay.base_url'), '/') . '/payment/status/*' => Http::response([
                'data' => [
                    'status' => 'success',
                    'transactionReference' => 'BEACH_6a501bd0df231',
                ],
            ], 200),
        ]);

        $ticket = Ticket::query()->create([
            'name' => 'Goodness Udochi',
            'email' => 'goodness@example.com',
            'phone' => '+2348000000000',
            'ticket_type' => 'VIP',
            'quantity' => 1,
            'amount' => 10000,
            'order_ref' => 'BEACH_6a501bd0df231',
            'payment_gateway' => 'tgipay',
            'paid' => false,
        ]);

        $result = app(TicketPaymentRefreshService::class)->refresh($ticket);

        $ticket->refresh();

        $this->assertTrue($result['ok']);
        $this->assertTrue($ticket->paid);
        $this->assertSame($ticket->order_ref, $ticket->payment_ref);
        $this->assertNotNull($ticket->qr_code_path);
        $this->assertFileExists(storage_path('app/public/' . $ticket->qr_code_path));

        Mail::assertSent(TicketReceiptMail::class, function (TicketReceiptMail $mail) use ($ticket): bool {
            return $mail->ticket->is($ticket);
        });
    }
}
