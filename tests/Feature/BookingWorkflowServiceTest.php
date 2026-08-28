<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingDeclinedMail;
use App\Models\Room;
use App\Services\BookingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_updates_status_and_sends_mail(): void
    {
        Mail::fake();

        $room = Room::query()->create([
            'room_name' => 'Deluxe Room 101',
            'room_category' => 'Deluxe',
            'room_price' => 75000,
            'is_booked' => 'no',
            'customer_name' => 'Ada Guest',
            'email' => 'ada@example.com',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
        ]);

        app(BookingWorkflowService::class)->confirm($room);

        $room->refresh();

        $this->assertSame('booked', $room->is_booked);
        $this->assertNull($room->rejection_reason);
        $this->assertTrue((bool) $room->mailsent);

        Mail::assertSent(BookingConfirmedMail::class, function (BookingConfirmedMail $mail) use ($room): bool {
            return $mail->room->is($room);
        });
    }

    public function test_reject_updates_status_stores_reason_and_sends_mail(): void
    {
        Mail::fake();

        $room = Room::query()->create([
            'room_name' => 'Suite Room 202',
            'room_category' => 'Suite',
            'room_price' => 120000,
            'is_booked' => 'no',
            'customer_name' => 'Ben Guest',
            'email' => 'ben@example.com',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(3),
        ]);

        app(BookingWorkflowService::class)->reject($room, 'No inventory for selected dates');

        $room->refresh();

        $this->assertSame('rejected', $room->is_booked);
        $this->assertSame('No inventory for selected dates', $room->rejection_reason);
        $this->assertTrue((bool) $room->mailsent);

        Mail::assertSent(BookingDeclinedMail::class, function (BookingDeclinedMail $mail) use ($room): bool {
            return $mail->room->is($room)
                && $mail->reason === 'No inventory for selected dates';
        });
    }
}
