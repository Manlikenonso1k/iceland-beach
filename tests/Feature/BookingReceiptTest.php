<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_success_page_shows_the_booking(): void
    {
        $booking = $this->paidBooking();

        $this->get(URL::signedRoute('booking.success', ['booking' => $booking->id]))
            ->assertOk()
            ->assertSee($booking->guest_email)
            ->assertSee('#' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT))
            ->assertSee('Gold Suite 7')
            // 3 nights at 95,000
            ->assertSee('285,000.00');
    }

    /**
     * A booking id is a small sequential integer, so without a signature the
     * success page and receipt would let anyone walk the range and read every
     * guest's name, email and phone.
     */
    public function test_unsigned_and_tampered_links_are_refused(): void
    {
        $booking = $this->paidBooking();

        $this->get('/booking/success/' . $booking->id)->assertForbidden();
        $this->get('/booking/receipt/' . $booking->id)->assertForbidden();

        $tampered = URL::signedRoute('booking.receipt', ['booking' => $booking->id]) . 'x';
        $this->get($tampered)->assertForbidden();
    }

    public function test_receipt_downloads_as_a_pdf(): void
    {
        $booking = $this->paidBooking();

        $response = $this->get(URL::signedRoute('booking.receipt', ['booking' => $booking->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString(
            'iceland-beach-receipt-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT) . '.pdf',
            (string) $response->headers->get('content-disposition'),
        );

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /** An unpaid request has taken no money, so there is nothing to receipt. */
    public function test_unpaid_booking_has_no_receipt(): void
    {
        $booking = $this->paidBooking();
        $booking->forceFill(['payment_status' => null, 'status' => 'pending'])->save();

        $this->get(URL::signedRoute('booking.success', ['booking' => $booking->id]))->assertNotFound();
        $this->get(URL::signedRoute('booking.receipt', ['booking' => $booking->id]))->assertNotFound();
    }

    private function paidBooking(): Booking
    {
        $room = Room::query()->create([
            'room_name' => 'Gold Suite 7',
            'room_category' => 'Gold',
            'room_price' => 95000,
            'is_booked' => 'booked',
        ]);

        return Booking::query()->create([
            'room_id' => $room->id,
            'guest_name' => 'Amaka Okafor',
            'guest_email' => 'amaka@example.com',
            'guest_phone' => '08012345678',
            'room_type' => $room->room_name,
            'check_in' => now()->addDays(20)->startOfDay(),
            'check_out' => now()->addDays(23)->startOfDay(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_reference' => 'BOOK-TESTREF01',
        ]);
    }
}
