<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\ServiceBooking;
use App\Models\SpaceBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Read-only booking visibility for the Hospi app.
 *
 * The role matrix is the point of this file. CEO, Manager and the space-host
 * role see bookings; every other role is refused; no token is 401. Enforced on
 * the SERVER — the app hiding a tab is not a control.
 */
class BookingVisibilityApiTest extends TestCase
{
    use RefreshDatabase;

    /** Roles the website lets see bookings. */
    private const ALLOWED_ROLES = ['CEO', 'Manager', 'Super Admin', 'Admin', 'Space Host', 'Host'];

    /** Roles that must never reach these endpoints. */
    private const FORBIDDEN_ROLES = ['Chef', 'Waiter', 'Scanner', 'Front Desk', 'Referral'];

    private const ENDPOINTS = [
        '/api/bookings/calendar?from=2026-01-01&to=2026-01-31',
        '/api/inventory/rooms',
        '/api/inventory/spaces',
    ];

    private function userWithRole(string $roleName): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($roleName, 'web');

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private int $roomSeq = 0;

    /** room.room_name carries a UNIQUE index, so each helper room needs its own. */
    private function room(?string $name = null, float $price = 80000): Room
    {
        $name ??= 'Deluxe 1' . str_pad((string) ++$this->roomSeq, 2, '0', STR_PAD_LEFT);

        return Room::query()->create([
            'room_name' => $name,
            'room_category' => 'Deluxe',
            'room_price' => $price,
            'is_booked' => 'no',
        ]);
    }

    private function space(string $name = 'Beach Cabana', float $price = 50000, ?string $category = 'Cabana'): ServiceBooking
    {
        return ServiceBooking::query()->create([
            'service_name' => $name,
            'service_category' => $category,
            'service_price' => $price,
            'is_booked' => 'no',
        ]);
    }

    private function roomBooking(array $overrides = []): Booking
    {
        $room = $overrides['_room'] ?? $this->room();
        unset($overrides['_room']);

        return Booking::query()->create(array_merge([
            'room_id' => $room->id,
            'guest_name' => 'Ada Lovelace',
            'guest_email' => 'ada@example.test',
            'guest_phone' => '08031234567',
            'room_type' => $room->room_name,
            'check_in' => now()->addDays(2),
            'check_out' => now()->addDays(4),
            'status' => 'confirmed',
        ], $overrides));
    }

    private function spaceBooking(array $overrides = []): SpaceBooking
    {
        $space = $overrides['_space'] ?? $this->space();
        unset($overrides['_space']);

        return SpaceBooking::query()->create(array_merge([
            'space_id' => $space->id,
            'space_name' => $space->service_name,
            'guest_name' => 'Grace Hopper',
            'guest_email' => 'grace@example.test',
            'guest_phone' => '08039998888',
            'guests' => 30,
            'check_in' => now()->addDays(3),
            'check_out' => now()->addDays(5),
            'status' => 'pending',
            'source' => 'manual',
        ], $overrides));
    }

    private function window(): string
    {
        return '?from=' . now()->subDay()->toDateString() . '&to=' . now()->addDays(30)->toDateString();
    }

    // --------------------------------------------------------- authentication

    public function test_no_token_is_rejected(): void
    {
        foreach (self::ENDPOINTS as $url) {
            $this->getJson($url)->assertUnauthorized();
        }

        $this->getJson('/api/bookings/rooms/1')->assertUnauthorized();
        $this->getJson('/api/bookings/spaces/1')->assertUnauthorized();
    }

    // ------------------------------------------------------------------ roles

    public function test_every_allowed_role_can_read_bookings(): void
    {
        $this->roomBooking();
        $this->spaceBooking();

        foreach (self::ALLOWED_ROLES as $roleName) {
            Sanctum::actingAs($this->userWithRole($roleName));

            $this->getJson('/api/bookings/calendar' . $this->window())
                ->assertOk()
                ->assertJsonCount(2, 'data');

            $this->getJson('/api/inventory/rooms')->assertOk();
            $this->getJson('/api/inventory/spaces')->assertOk();
        }
    }

    public function test_every_other_role_is_forbidden(): void
    {
        foreach (self::FORBIDDEN_ROLES as $roleName) {
            Sanctum::actingAs($this->userWithRole($roleName));

            foreach (self::ENDPOINTS as $url) {
                $this->getJson($url)->assertForbidden();
            }
        }
    }

    public function test_a_role_with_no_roles_at_all_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        foreach (self::ENDPOINTS as $url) {
            $this->getJson($url)->assertForbidden();
        }
    }

    public function test_there_is_no_write_route_for_bookings(): void
    {
        // The host role may change space bookings on the website, but nothing
        // here exposes that — so "view-only in the app" holds by construction
        // rather than by a check someone could forget.
        Sanctum::actingAs($this->userWithRole('Manager'));

        $booking = $this->roomBooking();
        $spaceBooking = $this->spaceBooking();

        foreach ([
            ['postJson', '/api/bookings/calendar'],
            ['patchJson', '/api/bookings/rooms/' . $booking->id],
            ['deleteJson', '/api/bookings/rooms/' . $booking->id],
            ['patchJson', '/api/bookings/spaces/' . $spaceBooking->id],
            ['deleteJson', '/api/bookings/spaces/' . $spaceBooking->id],
        ] as [$method, $url]) {
            $status = $this->{$method}($url)->getStatusCode();

            $this->assertContains($status, [404, 405], "{$method} {$url} should not be routable (got {$status})");
        }
    }

    // ----------------------------------------------------------- the calendar

    public function test_the_calendar_matches_the_web_status_set(): void
    {
        // The web widgets show pending AND confirmed, and nothing else. If this
        // drifts, app and web disagree for the same dates.
        $this->roomBooking(['guest_name' => 'Confirmed Guest', 'status' => 'confirmed']);
        $this->roomBooking(['guest_name' => 'Pending Guest', 'status' => 'pending']);
        $this->roomBooking(['guest_name' => 'Rejected Guest', 'status' => 'rejected']);
        $this->roomBooking(['guest_name' => 'Awaiting Payment', 'status' => 'pending_payment']);

        Sanctum::actingAs($this->userWithRole('Manager'));

        $names = collect($this->getJson('/api/bookings/calendar' . $this->window())->assertOk()->json('data'))
            ->pluck('guest_name');

        $this->assertCount(2, $names);
        $this->assertTrue($names->contains('Confirmed Guest'));
        $this->assertTrue($names->contains('Pending Guest'));
        $this->assertFalse($names->contains('Rejected Guest'));
        $this->assertFalse($names->contains('Awaiting Payment'));
    }

    public function test_the_type_filter_separates_rooms_and_spaces(): void
    {
        $this->roomBooking();
        $this->spaceBooking();

        Sanctum::actingAs($this->userWithRole('CEO'));

        $rooms = $this->getJson('/api/bookings/calendar' . $this->window() . '&type=rooms')->assertOk()->json('data');
        $spaces = $this->getJson('/api/bookings/calendar' . $this->window() . '&type=spaces')->assertOk()->json('data');
        $all = $this->getJson('/api/bookings/calendar' . $this->window() . '&type=all')->assertOk()->json('data');

        $this->assertSame(['room'], collect($rooms)->pluck('type')->unique()->all());
        $this->assertSame(['space'], collect($spaces)->pluck('type')->unique()->all());
        $this->assertCount(2, $all);
    }

    public function test_the_date_range_is_required_and_capped(): void
    {
        Sanctum::actingAs($this->userWithRole('Manager'));

        // Required: there must be no way to ask for the whole ledger.
        $this->getJson('/api/bookings/calendar')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from', 'to']);

        // Capped.
        $this->getJson('/api/bookings/calendar?from=2026-01-01&to=2027-01-01')
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'too wide'));
    }

    public function test_results_are_paginated_with_a_hard_ceiling(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->roomBooking(['guest_name' => 'Guest ' . $i]);
        }

        Sanctum::actingAs($this->userWithRole('Manager'));

        $response = $this->getJson('/api/bookings/calendar' . $this->window() . '&per_page=3')->assertOk();

        $this->assertCount(3, $response->json('data'));
        $this->assertSame(7, $response->json('meta.total'));
        $this->assertSame(3, $response->json('meta.last_page'));

        // A client cannot opt out of the ceiling.
        $this->getJson('/api/bookings/calendar' . $this->window() . '&per_page=5000')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_the_calendar_is_outside_the_requested_window(): void
    {
        $this->roomBooking(['guest_name' => 'In Window', 'check_in' => now()->addDays(2), 'check_out' => now()->addDays(3)]);
        $this->roomBooking(['guest_name' => 'Far Future', 'check_in' => now()->addDays(80), 'check_out' => now()->addDays(82)]);

        Sanctum::actingAs($this->userWithRole('Manager'));

        $names = collect($this->getJson(
            '/api/bookings/calendar?from=' . now()->toDateString() . '&to=' . now()->addDays(10)->toDateString(),
        )->assertOk()->json('data'))->pluck('guest_name');

        $this->assertTrue($names->contains('In Window'));
        $this->assertFalse($names->contains('Far Future'));
    }

    // -------------------------------------------------------------- the detail

    public function test_a_room_booking_detail_carries_contact_and_amount(): void
    {
        $room = $this->room('Deluxe 101');
        $booking = $this->roomBooking(['_room' => $room]);

        Sanctum::actingAs($this->userWithRole('Manager'));

        $this->getJson('/api/bookings/rooms/' . $booking->id)
            ->assertOk()
            ->assertJsonPath('data.guest_name', 'Ada Lovelace')
            ->assertJsonPath('data.guest_email', 'ada@example.test')
            ->assertJsonPath('data.guest_phone', '08031234567')
            // 80,000 x 2 nights, the same derivation the report uses.
            // assertJsonPath compares loosely; JSON decodes a whole number
            // as int, so 160000.0 would fail assertSame.
            ->assertJsonPath('data.amount', 160000)
            ->assertJsonPath('data.room.name', 'Deluxe 101');
    }

    public function test_a_space_booking_detail_shows_free_for_a_complimentary_space(): void
    {
        $complimentary = $this->space('Kids Area', 0, ServiceBooking::COMPLIMENTARY_CATEGORY);
        $booking = $this->spaceBooking(['_space' => $complimentary]);

        Sanctum::actingAs($this->userWithRole('Manager'));

        $this->getJson('/api/bookings/spaces/' . $booking->id)
            ->assertOk()
            ->assertJsonPath('data.amount', 0)
            ->assertJsonPath('data.amount_label', 'Free');
    }

    public function test_an_unknown_booking_id_is_a_404_not_a_leak(): void
    {
        Sanctum::actingAs($this->userWithRole('Manager'));

        $this->getJson('/api/bookings/rooms/999999')->assertNotFound();
        $this->getJson('/api/bookings/spaces/999999')->assertNotFound();
    }

    public function test_a_forbidden_role_cannot_read_a_detail_by_id(): void
    {
        // IDOR check: the gate is on the route, so guessing an id changes nothing.
        $booking = $this->roomBooking();
        $spaceBooking = $this->spaceBooking();

        Sanctum::actingAs($this->userWithRole('Chef'));

        $this->getJson('/api/bookings/rooms/' . $booking->id)->assertForbidden();
        $this->getJson('/api/bookings/spaces/' . $spaceBooking->id)->assertForbidden();
    }

    // ------------------------------------------------------------- receipts

    public function test_receipt_links_are_signed_and_only_present_when_real(): void
    {
        Storage::fake('local');

        $unpaid = $this->roomBooking(['guest_name' => 'Unpaid']);
        $paid = $this->roomBooking(['guest_name' => 'Paid', 'payment_status' => 'paid']);

        Sanctum::actingAs($this->userWithRole('Manager'));

        $rows = collect($this->getJson('/api/bookings/calendar' . $this->window() . '&type=rooms')->assertOk()->json('data'));

        $this->assertNull($rows->firstWhere('guest_name', 'Unpaid')['receipt_url']);

        $paidUrl = $rows->firstWhere('guest_name', 'Paid')['receipt_url'];

        $this->assertNotNull($paidUrl);
        // Signed, never a guessable public path.
        $this->assertStringContainsString('signature=', $paidUrl);
        $this->assertStringNotContainsString('/storage/', $paidUrl);
    }

    // ------------------------------------------------------------- inventory

    public function test_inventory_returns_full_image_urls_and_prices(): void
    {
        Storage::fake('public');

        $room = $this->room('Suite 5', 120000);
        Storage::disk('public')->put('rooms/main/suite.jpg', 'x');
        $room->update(['main_image' => 'rooms/main/suite.jpg']);

        $space = $this->space('Marquee', 250000);
        Storage::disk('public')->put('spaces/main/marquee.jpg', 'x');
        $space->update(['main_image' => 'spaces/main/marquee.jpg']);

        Sanctum::actingAs($this->userWithRole('CEO'));

        $roomRow = collect($this->getJson('/api/inventory/rooms')->assertOk()->json('data'))
            ->firstWhere('name', 'Suite 5');

        $this->assertStringContainsString('/storage/rooms/main/suite.jpg', $roomRow['image_url']);
        $this->assertEquals(120000, $roomRow['price']);
        $this->assertSame('₦120,000.00', $roomRow['price_label']);

        $spaceRow = collect($this->getJson('/api/inventory/spaces')->assertOk()->json('data'))
            ->firstWhere('name', 'Marquee');

        $this->assertStringContainsString('/storage/spaces/main/marquee.jpg', $spaceRow['image_url']);
        $this->assertSame('₦250,000.00', $spaceRow['price_label']);
    }

    public function test_inventory_marks_a_confirmed_hold_unavailable(): void
    {
        // Must match the website: ONLY a confirmed booking holds a unit.
        $room = $this->room('Held Room');
        Booking::query()->create([
            'room_id' => $room->id,
            'guest_name' => 'Holder',
            'guest_phone' => '080',
            'room_type' => 'Held Room',
            'check_in' => now()->addDays(2),
            'check_out' => now()->addDays(4),
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($this->userWithRole('Manager'));

        $url = '/api/inventory/rooms?from=' . now()->addDays(2)->toDateString()
            . '&to=' . now()->addDays(4)->toDateString();

        $row = collect($this->getJson($url)->assertOk()->json('data'))->firstWhere('name', 'Held Room');

        $this->assertFalse($row['is_available']);
    }

    public function test_a_complimentary_space_reads_free_in_inventory(): void
    {
        $this->space('Kids Area', 0, ServiceBooking::COMPLIMENTARY_CATEGORY);

        Sanctum::actingAs($this->userWithRole('Manager'));

        $row = collect($this->getJson('/api/inventory/spaces')->assertOk()->json('data'))
            ->firstWhere('name', 'Kids Area');

        $this->assertSame('Free', $row['price_label']);
        $this->assertTrue($row['is_complimentary']);
        $this->assertNull($row['price_unit']);
    }
}
