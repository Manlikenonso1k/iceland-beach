<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Guards on POST /api/bookings.
 *
 * Two separate limits, easily confused:
 *
 *   - The DUPLICATE check (same email + room + dates) always applies. It exists
 *     to absorb double-tapped buttons and refreshed tabs, so it must fire
 *     whether or not payment is switched on.
 *
 *   - The ONE-ACTIVE-BOOKING check applies only while payment is off. With
 *     nothing to pay, a single address could otherwise flood the staff review
 *     queue for free. Paying guests are self-limiting and must stay free to
 *     book several rooms in one sitting — an honest family booking two rooms
 *     must not be turned away.
 *
 * On top of both sits throttle:10,1 on the route itself, which is per IP and
 * therefore the only guard that catches a script cycling through addresses.
 */
class BookingRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // requestBooking() emails the guest; none of these assertions care.
        Mail::fake();
    }

    private function room(string $name): Room
    {
        return Room::query()->create([
            'room_name' => $name,
            'room_category' => 'Premium',
            'room_price' => 90000,
            'is_booked' => 'no',
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Room $room, array $overrides = []): array
    {
        return array_merge([
            'room_id' => $room->id,
            'guest_name' => 'Chidi Guest',
            'guest_email' => 'chidi@example.com',
            'guest_phone' => '08000000000',
            'guests' => 2,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
        ], $overrides);
    }

    /** Switch the paid flow on and stub Paystack so no real call is made. */
    private function enablePayment(): void
    {
        Setting::set('booking_payment_required', true);
        config(['services.paystack.secret_key' => 'sk_test_fake']);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/fake'],
            ]),
        ]);
    }

    public function test_payment_off_allows_only_one_active_booking_per_email(): void
    {
        $first = $this->room('Premium Room 1');
        $second = $this->room('Premium Room 2');

        $this->postJson('/api/bookings', $this->payload($first))
            ->assertCreated()
            ->assertJsonPath('requires_payment', false);

        // A different room and different dates — blocked purely because the
        // address already holds a live request.
        $this->postJson('/api/bookings', $this->payload($second, [
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
        ]))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'already have an active booking'));

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_a_rejected_booking_does_not_block_the_guest_from_trying_again(): void
    {
        // Only 'pending' and 'confirmed' are live. A guest turned away must be
        // able to request different dates immediately.
        $room = $this->room('Premium Room 1');

        $this->postJson('/api/bookings', $this->payload($room))->assertCreated();

        Booking::query()->firstOrFail()->update(['status' => 'rejected']);

        $this->postJson('/api/bookings', $this->payload($room, [
            'check_in' => now()->addDays(20)->toDateString(),
            'check_out' => now()->addDays(22)->toDateString(),
        ]))->assertCreated();

        $this->assertSame(2, Booking::query()->count());
    }

    public function test_payment_on_lets_one_email_book_several_rooms(): void
    {
        $this->enablePayment();

        $first = $this->room('Premium Room 1');
        $second = $this->room('Premium Room 2');

        // Same email, same dates, different rooms — the family case.
        $this->postJson('/api/bookings', $this->payload($first))
            ->assertCreated()
            ->assertJsonPath('requires_payment', true)
            ->assertJsonPath('authorization_url', 'https://checkout.paystack.com/fake');

        $this->postJson('/api/bookings', $this->payload($second))
            ->assertCreated()
            ->assertJsonPath('requires_payment', true);

        $this->assertSame(2, Booking::query()->where('status', 'pending_payment')->count());
    }

    public function test_the_exact_same_room_and_dates_are_refused_even_with_payment_on(): void
    {
        $this->enablePayment();

        $room = $this->room('Premium Room 1');

        $this->postJson('/api/bookings', $this->payload($room))->assertCreated();

        // pending_payment counts — the guest may still have Paystack open.
        $this->postJson('/api/bookings', $this->payload($room))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'booking for this room on those dates'));

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_the_route_throttles_a_flood_of_attempts_from_one_address(): void
    {
        $room = $this->room('Premium Room 1');

        // The throttle sits in front of validation, so rejected payloads still
        // consume the budget — which is what makes it useful against a script.
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/bookings', $this->payload($room, [
                'guest_email' => "flood{$attempt}@example.com",
                'check_in' => 'not-a-date',
            ]))->assertStatus(422);
        }

        $this->postJson('/api/bookings', $this->payload($room, [
            'guest_email' => 'flood11@example.com',
        ]))->assertStatus(429);
    }
}
