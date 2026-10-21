<?php

namespace Tests\Feature;

use App\Models\ServiceBooking;
use App\Models\Setting;
use App\Models\SpaceBooking;
use App\Services\SpaceBookingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Spaces in the "Complimentary" category carry no charge and must read "Free"
 * everywhere a price would otherwise appear, instead of "₦0.00".
 *
 * The distinction that matters: a Complimentary space is DELIBERATELY free,
 * while a priced space sitting at 0.00 is almost certainly one whose price
 * nobody filled in. The first books straight through; the second is still
 * refused rather than quietly given away.
 */
class ComplimentarySpaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function space(string $name, ?string $category, float $price): ServiceBooking
    {
        return ServiceBooking::query()->create([
            'service_name' => $name,
            'service_category' => $category,
            'service_price' => $price,
            'is_booked' => 'no',
        ]);
    }

    private function complimentary(string $name = 'Kids Play Area'): ServiceBooking
    {
        return $this->space($name, ServiceBooking::COMPLIMENTARY_CATEGORY, 0);
    }

    // ------------------------------------------------------------ the rule

    public function test_a_complimentary_space_reads_free(): void
    {
        $space = $this->complimentary();

        $this->assertTrue($space->isComplimentary());
        $this->assertTrue($space->isFree());
        $this->assertSame('Free', $space->priceLabel());
    }

    public function test_the_category_match_is_case_and_space_insensitive(): void
    {
        // The category is free text with suggestions, not an enum, so staff
        // will type it however they like.
        foreach (['complimentary', 'COMPLIMENTARY', '  Complimentary  '] as $typed) {
            $space = $this->space('Variant ' . $typed, $typed, 0);

            $this->assertTrue($space->isComplimentary(), "'{$typed}' should count as complimentary");
            $this->assertSame('Free', $space->priceLabel());
        }
    }

    public function test_a_complimentary_space_with_a_price_still_reads_free(): void
    {
        // Belt and braces: if a price is left behind after re-categorising a
        // space, the category wins. It is the statement of intent.
        $space = $this->space('Legacy Priced', ServiceBooking::COMPLIMENTARY_CATEGORY, 75000);

        $this->assertTrue($space->isFree());
        $this->assertSame('Free', $space->priceLabel());
    }

    public function test_a_priced_space_still_shows_its_price(): void
    {
        $space = $this->space('Sunset Marquee', 'Marquee', 250000);

        $this->assertFalse($space->isComplimentary());
        $this->assertFalse($space->isFree());
        $this->assertSame('₦250,000.00', $space->priceLabel());
    }

    public function test_a_priced_space_at_zero_reads_free_but_is_not_complimentary(): void
    {
        // Reads Free to a guest, but is NOT complimentary — which is what lets
        // the booking endpoint refuse it as a misconfiguration.
        $space = $this->space('Forgot The Price', 'Cabana', 0);

        $this->assertFalse($space->isComplimentary());
        $this->assertTrue($space->isFree());
        $this->assertSame('Free', $space->priceLabel());
    }

    // ------------------------------------------------------------ the API

    public function test_the_catalogue_api_computes_the_label_server_side(): void
    {
        // Computed on the server so the React pages do not re-implement it.
        $this->complimentary();
        $this->space('Sunset Marquee', 'Marquee', 250000);

        $response = $this->getJson('/api/services')->assertOk();

        $free = collect($response->json())->firstWhere('service_name', 'Kids Play Area');
        $paid = collect($response->json())->firstWhere('service_name', 'Sunset Marquee');

        $this->assertTrue($free['is_complimentary']);
        $this->assertTrue($free['is_free']);
        $this->assertSame('Free', $free['price_label']);

        $this->assertFalse($paid['is_complimentary']);
        $this->assertFalse($paid['is_free']);
        $this->assertSame('₦250,000.00', $paid['price_label']);
    }

    // ------------------------------------------------------- payment flow

    private function enablePayment(): void
    {
        Setting::set('space_payment_required', true);
        config(['services.paystack.secret_key' => 'sk_test_fake']);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/fake'],
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(ServiceBooking $space): array
    {
        return [
            'service_id' => $space->id,
            'guest_name' => 'Ada Guest',
            'guest_email' => 'ada@example.test',
            'guests' => 12,
            'check_in' => now()->addDays(4)->toDateString(),
            'check_out' => now()->addDays(6)->toDateString(),
        ];
    }

    public function test_a_complimentary_space_books_without_payment_even_when_the_toggle_is_on(): void
    {
        // The bug this prevents: Paystack rejects a zero amount, so a free
        // space would have been 422'd as "no price set" while pay-to-confirm
        // was enabled — refusing a perfectly valid booking.
        $this->enablePayment();

        $space = $this->complimentary();

        $this->postJson('/api/service-bookings', $this->payload($space))
            ->assertCreated()
            ->assertJsonPath('requires_payment', false);

        $booking = SpaceBooking::query()->firstOrFail();

        $this->assertSame('pending', $booking->status);
        $this->assertNull($booking->payment_reference);

        // Nothing was sent to Paystack at all.
        Http::assertNothingSent();
    }

    public function test_a_priced_space_at_zero_is_still_refused_as_a_misconfiguration(): void
    {
        $this->enablePayment();

        $space = $this->space('Forgot The Price', 'Cabana', 0);

        $this->postJson('/api/service-bookings', $this->payload($space))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'no price set'));

        $this->assertSame(0, SpaceBooking::query()->count());
    }

    public function test_a_priced_space_still_goes_to_paystack(): void
    {
        $this->enablePayment();

        $space = $this->space('Sunset Marquee', 'Marquee', 250000);

        $this->postJson('/api/service-bookings', $this->payload($space))
            ->assertCreated()
            ->assertJsonPath('requires_payment', true);
    }

    // ----------------------------------------------------------- the report

    public function test_the_report_prints_free_rather_than_zero(): void
    {
        $free = $this->complimentary();
        $paid = $this->space('Sunset Marquee', 'Marquee', 100000);

        foreach ([$free, $paid] as $space) {
            SpaceBooking::query()->create([
                'space_id' => $space->id,
                'space_name' => $space->service_name,
                'guest_name' => 'Guest of ' . $space->service_name,
                'guest_phone' => '08000000000',
                'guests' => 10,
                'check_in' => now()->setTime(10, 0),
                'check_out' => now()->addDay()->setTime(10, 0),
                'status' => 'confirmed',
            ]);
        }

        $service = app(SpaceBookingReportService::class);
        [$start, $end] = $service->range(['period' => 'daily', 'date' => now()->toDateString()]);

        $rows = $service->rows($start, $end);

        $freeRow = $rows->firstWhere('room', 'Kids Play Area');
        $paidRow = $rows->firstWhere('room', 'Sunset Marquee');

        $this->assertSame('Free', $freeRow['amount_label']);
        $this->assertSame('100,000.00', $paidRow['amount_label']);

        // The numeric amount is untouched, because the TOTAL row sums it and a
        // complimentary booking must contribute exactly zero.
        $this->assertSame(0.0, (float) $freeRow['amount']);
        $this->assertSame(100000.0, (float) $rows->sum('amount'));
    }

    public function test_the_room_report_is_unaffected(): void
    {
        // amount_label is opt-in; the room report supplies none and must still
        // render its numbers.
        $response = app(\App\Services\BookingReportService::class)->download([
            'period' => 'daily',
            'date' => now()->toDateString(),
        ]);

        ob_start();
        $response->sendContent();
        $pdf = (string) ob_get_clean();

        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
