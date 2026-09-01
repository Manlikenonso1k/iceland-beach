<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingDeclinedMail;
use App\Models\Booking;
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
            return $mail->record->is($room);
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
            return $mail->record->is($room)
                && $mail->reason === 'No inventory for selected dates';
        });
    }

    public function test_rejecting_a_booking_releases_the_room_and_emails_the_guest(): void
    {
        Mail::fake();

        $booking = $this->makeBooking('Beach Apartment 3', 'pending');
        $booking->room->forceFill(['is_booked' => 'booked'])->save();

        app(BookingWorkflowService::class)->rejectBooking($booking, 'Fully booked for those dates');

        $booking->refresh();

        $this->assertSame('rejected', $booking->status);
        $this->assertSame('Fully booked for those dates', $booking->rejection_reason);
        // A rejection must never leave the room held.
        $this->assertSame('no', $booking->room->fresh()->is_booked);

        Mail::assertSent(BookingDeclinedMail::class, fn (BookingDeclinedMail $mail): bool => $mail->record->is($booking)
            && $mail->reason === 'Fully booked for those dates');
    }

    public function test_paying_auto_confirms_the_booking_and_holds_the_room(): void
    {
        Mail::fake();

        $booking = $this->makeBooking('Premium Room 5', 'pending_payment', 'pending');

        app(BookingWorkflowService::class)->markBookingPaid($booking);

        $booking->refresh();

        $this->assertSame('paid', $booking->payment_status);
        // Payment auto-approves — the booking skips the staff review queue.
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('booked', $booking->room->fresh()->is_booked);

        Mail::assertSent(BookingConfirmedMail::class, 1);
    }

    public function test_marking_paid_twice_does_not_email_the_guest_twice(): void
    {
        Mail::fake();

        $booking = $this->makeBooking('Gold Room 9', 'pending_payment', 'pending');
        $workflow = app(BookingWorkflowService::class);

        // The Paystack callback and the webhook routinely both fire.
        $workflow->markBookingPaid($booking);
        $workflow->markBookingPaid($booking->fresh());

        Mail::assertSent(BookingConfirmedMail::class, 1);
    }

    private function makeBooking(string $roomName, string $status, ?string $paymentStatus = null): Booking
    {
        $room = Room::query()->create([
            'room_name' => $roomName,
            'room_category' => 'Premium',
            'room_price' => 90000,
            'is_booked' => 'no',
        ]);

        return Booking::query()->create([
            'room_id' => $room->id,
            'guest_name' => 'Chidi Guest',
            'guest_email' => 'chidi@example.com',
            'guest_phone' => '08000000000',
            'room_type' => $room->room_name,
            'check_in' => now()->addDay(),
            'check_out' => now()->addDays(3),
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }
}
