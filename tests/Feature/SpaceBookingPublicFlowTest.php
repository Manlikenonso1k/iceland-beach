<?php

namespace Tests\Feature;

use App\Models\ServiceBooking;
use App\Models\Setting;
use App\Models\SpaceBooking;
use App\Services\SpaceBookingReportService;
use App\Services\SpaceBookingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The public space booking endpoint and the two flows the Super Admin can
 * switch between.
 *
 * POST /api/service-bookings previously sent two emails and persisted NOTHING,
 * so nothing could be confirmed, denied or reported on. The first test here is
 * the one that matters: a guest request now lands in the ledger.
 */
class SpaceBookingPublicFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function space(string $name = 'Beach Cabana 1', float $price = 50000): ServiceBooking
    {
        return ServiceBooking::query()->create([
            'service_name' => $name,
            'service_category' => 'Cabana',
            'service_price' => $price,
            'is_booked' => 'no',
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(ServiceBooking $space, array $overrides = []): array
    {
        return array_merge([
            'service_id' => $space->id,
            'guest_name' => 'Ada Guest',
            'guest_email' => 'ada@example.test',
            'guest_phone' => '08031234567',
            'guests' => 25,
            'check_in' => now()->addDays(4)->toDateString(),
            'check_out' => now()->addDays(6)->toDateString(),
        ], $overrides);
    }

    private function enablePayment(): void
    {
        Setting::set('space_payment_required', true);
        config(['services.paystack.secret_key' => 'sk_test_fake']);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/space-fake'],
            ]),
        ]);
    }

    public function test_a_guest_booking_lands_in_the_ledger_and_shows_for_the_host(): void
    {
        $space = $this->space();

        $this->postJson('/api/service-bookings', $this->payload($space))
            ->assertCreated()
            ->assertJsonPath('requires_payment', false);

        // This is the whole point of the change — previously zero rows.
        $booking = SpaceBooking::query()->firstOrFail();

        $this->assertSame('pending', $booking->status);
        $this->assertSame($space->id, $booking->space_id);
        $this->assertSame('Beach Cabana 1', $booking->space_name);
        $this->assertSame(25, $booking->guests);

        // Pending never holds the space, so the catalogue stays available.
        $this->assertSame('no', $space->fresh()->is_booked);
    }

    public function test_only_a_confirmed_booking_blocks_the_same_dates(): void
    {
        $space = $this->space();

        // A pending request must not make the space look taken.
        $this->postJson('/api/service-bookings', $this->payload($space))->assertCreated();

        $this->postJson('/api/service-bookings', $this->payload($space, [
            'guest_email' => 'ben@example.test',
        ]))->assertCreated();

        app(SpaceBookingWorkflowService::class)->confirmBooking(SpaceBooking::query()->firstOrFail());

        // Now the dates are genuinely held.
        $this->postJson('/api/service-bookings', $this->payload($space, [
            'guest_email' => 'cara@example.test',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'already booked'));
    }

    public function test_the_identical_request_twice_is_refused(): void
    {
        $space = $this->space();

        $this->postJson('/api/service-bookings', $this->payload($space))->assertCreated();

        $this->postJson('/api/service-bookings', $this->payload($space))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'booking for this space on those dates'));

        $this->assertSame(1, SpaceBooking::query()->count());
    }

    public function test_email_confirmation_is_the_default_flow(): void
    {
        // No setting written at all — the default must be the email flow, so
        // turning the feature on never silently starts charging guests.
        $space = $this->space();

        $this->postJson('/api/service-bookings', $this->payload($space))
            ->assertCreated()
            ->assertJsonPath('requires_payment', false);

        $booking = SpaceBooking::query()->firstOrFail();

        $this->assertSame('pending', $booking->status);
        $this->assertNull($booking->payment_reference);
        $this->assertNull($booking->payment_status);
    }

    public function test_switching_the_toggle_on_sends_the_guest_to_paystack(): void
    {
        $this->enablePayment();

        $space = $this->space();

        $this->postJson('/api/service-bookings', $this->payload($space))
            ->assertCreated()
            ->assertJsonPath('requires_payment', true)
            ->assertJsonPath('authorization_url', 'https://checkout.paystack.com/space-fake');

        $booking = SpaceBooking::query()->firstOrFail();

        // Held out of the Space Host's queue until Paystack confirms.
        $this->assertSame('pending_payment', $booking->status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertStringStartsWith('SPACE-', (string) $booking->payment_reference);
    }

    public function test_the_space_reference_prefix_keeps_the_room_flow_distinct(): void
    {
        // PaystackWebhookController dispatches on this prefix, and Paystack
        // allows only one webhook URL per mode. A SPACE- reference must never
        // be mistaken for a room booking or a ticket.
        $this->enablePayment();

        $this->postJson('/api/service-bookings', $this->payload($this->space()))->assertCreated();

        $reference = (string) SpaceBooking::query()->firstOrFail()->payment_reference;

        $this->assertTrue(str_starts_with($reference, 'SPACE-'));
        $this->assertFalse(str_starts_with($reference, 'BOOK-'));
    }

    public function test_paying_auto_confirms_and_is_idempotent(): void
    {
        $this->enablePayment();

        $this->postJson('/api/service-bookings', $this->payload($this->space()))->assertCreated();

        $booking = SpaceBooking::query()->firstOrFail();
        $workflow = app(SpaceBookingWorkflowService::class);

        // The callback and the webhook routinely both fire.
        $workflow->markBookingPaid($booking);
        $workflow->markBookingPaid($booking->fresh());

        $booking->refresh();

        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('booked', $booking->space->fresh()->is_booked);

        // One confirmation only — a guest must never be emailed twice.
        Mail::assertSent(\App\Mail\BookingConfirmedMail::class, 1);
    }

    public function test_a_zero_priced_space_is_refused_rather_than_sent_to_paystack(): void
    {
        // Paystack rejects a zero amount, which would surface as an opaque 500.
        $this->enablePayment();

        $this->postJson('/api/service-bookings', $this->payload($this->space('Free Space', 0)))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'no price set'));

        // No orphan holding a reference the guest can never pay.
        $this->assertSame(0, SpaceBooking::query()->count());
    }

    public function test_report_totals_match_the_bookings_in_the_database(): void
    {
        $space = $this->space('Beach Cabana 1', 50000);
        $other = $this->space('Marquee', 120000);

        // 2 nights x 50,000 = 100,000
        SpaceBooking::query()->create([
            'space_id' => $space->id, 'space_name' => $space->service_name,
            'guest_name' => 'Ada', 'guest_phone' => '080', 'guests' => 10,
            'check_in' => now()->setTime(10, 0), 'check_out' => now()->addDays(2)->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        // 1 night x 120,000 = 120,000
        SpaceBooking::query()->create([
            'space_id' => $other->id, 'space_name' => $other->service_name,
            'guest_name' => 'Ben', 'guest_phone' => '081', 'guests' => 50,
            'check_in' => now()->setTime(12, 0), 'check_out' => now()->addDay()->setTime(12, 0),
            'status' => 'pending',
        ]);

        // Rejected never became a booking, so it must not reach the figures.
        SpaceBooking::query()->create([
            'space_id' => $other->id, 'space_name' => $other->service_name,
            'guest_name' => 'Cara', 'guest_phone' => '082', 'guests' => 5,
            'check_in' => now()->setTime(14, 0), 'check_out' => now()->addDay()->setTime(14, 0),
            'status' => 'rejected',
        ]);

        $service = app(SpaceBookingReportService::class);
        [$start, $end] = $service->range(['period' => 'daily', 'date' => now()->toDateString()]);

        $rows = $service->rows($start, $end);

        $this->assertCount(2, $rows);
        $this->assertSame(220000.0, (float) $rows->sum('amount'));
    }

    public function test_the_monthly_report_window_covers_the_whole_month(): void
    {
        $service = app(SpaceBookingReportService::class);

        [$start, $end, $label] = $service->range([
            'period' => 'monthly',
            'month' => 6,
            'year' => 2026,
        ]);

        $this->assertSame('2026-06-01', $start->toDateString());
        $this->assertSame('2026-06-30', $end->toDateString());
        $this->assertStringContainsString('June 2026', $label);
    }
}
