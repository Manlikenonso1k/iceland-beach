<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\Room;
use App\Models\ServiceBooking;
use App\Models\SpaceBooking;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VenueTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Security regressions for the whole /api surface (audit of 2026-10-21).
 *
 * Each test here is the proof for one finding in PROGRESS.md § "Security audit
 * — 2026-10-21". Three of them assert fixes made in that audit; the rest pin
 * controls that are currently correct and whose quiet removal would be a leak
 * nothing else in the suite would catch.
 */
class ApiSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    /** Guest PII that lives on the catalogue tables and must never be served publicly. */
    private const ROOM_PII_KEYS = [
        'customer_name', 'email', 'start_date', 'end_date', 'total_price',
        'rejection_reason', 'mailsent',
    ];

    private const SERVICE_PII_KEYS = [
        'customers_name', 'customers_email', 'signin', 'signout',
        'no_of_people', 'mailsent',
    ];

    private function makeTenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug).' Beach',
            'slug' => $slug,
            'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen', 'barbecue'],
            'timezone' => 'Africa/Lagos',
            'is_active' => true,
        ]);
    }

    private function makeUser(string $role, ?Tenant $tenant, ?string $department = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($role, 'web');

        $user = User::query()->create([
            'name' => $role.' '.($tenant->slug ?? 'none'),
            'email' => strtolower(str_replace(' ', '', $role)).'-'.uniqid().'@hospi.test',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant?->id,
            'department' => $department,
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * A single test makes several in-process requests as different users; the
     * guard memoizes the first-resolved user, so flush it each time (a non-issue
     * in production, where every request is its own process).
     */
    private function auth(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('audit')->plainTextToken);
    }

    // ── Finding: public catalogue endpoints must not leak guest PII ─────────

    /**
     * The highest-value check in the audit. `room` and `services` are catalogue
     * tables that ALSO carry the legacy "who is in it right now" columns. Both
     * public endpoints are safe today only because they name their columns in a
     * select() — widening either one to select('*'), or dropping the select,
     * publishes a named guest's email to the open internet with no token.
     */
    public function test_public_rooms_endpoint_never_returns_guest_pii(): void
    {
        Room::query()->create([
            'room_name' => 'Deluxe Audit',
            'room_category' => 'Deluxe',
            'room_price' => 80000,
            'is_booked' => 'booked',
            'customer_name' => 'Ada Walk-In',
            'email' => 'ada@example.com',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-04',
            'total_price' => 240000,
        ]);

        $response = $this->getJson('/api/rooms')->assertOk();
        $body = $response->getContent();

        foreach (self::ROOM_PII_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $response->json(0), "/api/rooms exposes room.$key");
        }

        $this->assertStringNotContainsString('Ada Walk-In', $body);
        $this->assertStringNotContainsString('ada@example.com', $body);
        // The catalogue itself still works — this is not a test that passes by
        // the endpoint returning nothing.
        $this->assertSame('Deluxe Audit', $response->json('0.room_name'));
    }

    public function test_public_services_endpoint_never_returns_guest_pii(): void
    {
        ServiceBooking::query()->create([
            'service_name' => 'Cabana Audit',
            'service_category' => 'Cabana',
            'service_price' => 50000,
            'is_booked' => 'booked',
            'customers_name' => 'Bola Occupant',
            'customers_email' => 'bola@example.com',
            'signin' => '2026-05-01 09:00:00',
            'signout' => '2026-05-01 18:00:00',
            'no_of_people' => 6,
        ]);

        $response = $this->getJson('/api/services')->assertOk();
        $body = $response->getContent();

        foreach (self::SERVICE_PII_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $response->json(0), "/api/services exposes services.$key");
        }

        $this->assertStringNotContainsString('Bola Occupant', $body);
        $this->assertStringNotContainsString('bola@example.com', $body);
        $this->assertSame('Cabana Audit', $response->json('0.service_name'));
    }

    // ── Fix: cross-tenant read of the resort's booking ledger ──────────────

    /** @return array{0: Booking, 1: SpaceBooking} */
    private function resortBookings(): array
    {
        $room = Room::query()->create([
            'room_name' => 'Deluxe Ledger',
            'room_category' => 'Deluxe',
            'room_price' => 80000,
            'is_booked' => 'no',
        ]);

        $booking = Booking::query()->create([
            'room_id' => $room->id,
            'guest_name' => 'Jane Resort',
            'guest_email' => 'jane@example.com',
            'guest_phone' => '08011112222',
            'room_type' => 'Deluxe',
            'check_in' => '2026-02-01',
            'check_out' => '2026-02-03',
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $space = ServiceBooking::query()->create([
            'service_name' => 'Cabana Ledger',
            'service_category' => 'Cabana',
            'service_price' => 50000,
            'is_booked' => 'no',
        ]);

        $spaceBooking = SpaceBooking::query()->create([
            'space_id' => $space->id,
            'space_name' => 'Cabana Ledger',
            'guest_name' => 'Joe Space',
            'guest_email' => 'joe@example.com',
            'guest_phone' => '08033334444',
            'guests' => 4,
            'check_in' => '2026-02-01',
            'check_out' => '2026-02-02',
            'status' => 'confirmed',
        ]);

        return [$booking, $spaceBooking];
    }

    /**
     * Before the fix this returned 200 with guest_email, guest_phone and a
     * VALID signed receipt URL to a manager of a different business — the role
     * gate passed (she really is a Manager) and `bookings` has no tenant_id for
     * BelongsToTenant to scope, so nothing else was checking.
     */
    public function test_another_tenants_manager_cannot_read_resort_booking_pii(): void
    {
        $this->makeTenant('iceland-beach');
        $other = $this->makeTenant('bravo-beach');
        $manager = $this->makeUser('Manager', $other);

        [$booking, $spaceBooking] = $this->resortBookings();

        $this->auth($manager)->getJson('/api/bookings/rooms/'.$booking->id)->assertForbidden();
        $this->auth($manager)->getJson('/api/bookings/spaces/'.$spaceBooking->id)->assertForbidden();

        $calendar = $this->auth($manager)
            ->getJson('/api/bookings/calendar?from=2026-01-01&to=2026-03-01')
            ->assertForbidden();

        $this->assertStringNotContainsString('jane@example.com', $calendar->getContent());
        $this->assertStringNotContainsString('Jane Resort', $calendar->getContent());
    }

    /** The host role is gated the same way — it is the other role with calendar access. */
    public function test_another_tenants_space_host_cannot_read_resort_bookings(): void
    {
        $this->makeTenant('iceland-beach');
        $other = $this->makeTenant('bravo-beach');
        $host = $this->makeUser('Space Host', $other);

        [$booking] = $this->resortBookings();

        $this->auth($host)->getJson('/api/bookings/rooms/'.$booking->id)->assertForbidden();
    }

    /** The resort's own staff are untouched — the fix must not cost Iceland its calendar. */
    public function test_the_resort_tenants_manager_still_sees_bookings(): void
    {
        $resort = $this->makeTenant('iceland-beach');
        $manager = $this->makeUser('Manager', $resort);

        [$booking] = $this->resortBookings();

        $this->auth($manager)
            ->getJson('/api/bookings/rooms/'.$booking->id)
            ->assertOk()
            ->assertJsonPath('data.guest_email', 'jane@example.com');

        $this->auth($manager)
            ->getJson('/api/bookings/calendar?from=2026-01-01&to=2026-03-01')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    /** Legacy website admins predate tenants and have none. They are the resort. */
    public function test_a_tenantless_legacy_admin_still_sees_bookings(): void
    {
        $this->makeTenant('iceland-beach');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Super Admin', 'web');
        $admin = User::query()->create([
            'name' => 'Legacy Admin',
            'email' => 'legacy-'.uniqid().'@icelandbeach.com',
            'password' => Hash::make('password'),
            'tenant_id' => null,
        ]);
        $admin->assignRole('Super Admin');

        [$booking] = $this->resortBookings();

        $this->auth($admin)->getJson('/api/bookings/rooms/'.$booking->id)->assertOk();
    }

    /**
     * A misconfigured RESORT_TENANT_SLUG must not lock the resort out of its own
     * calendar, so the gate fails OPEN when no tenant matches the slug. This
     * pins that choice so it stays deliberate rather than being discovered.
     */
    public function test_the_gate_fails_open_when_the_resort_tenant_does_not_exist(): void
    {
        config(['resort.tenant_slug' => 'no-such-tenant-slug']);

        $tenant = $this->makeTenant('bravo-beach');
        $manager = $this->makeUser('Manager', $tenant);

        [$booking] = $this->resortBookings();

        $this->auth($manager)->getJson('/api/bookings/rooms/'.$booking->id)->assertOk();
    }

    // ── Finding: tenant isolation on the Hospi tables ──────────────────────

    /**
     * Every id-taking Hospi route must 404 — not 403, and certainly not 200 —
     * for a record belonging to another business. The isolation comes from
     * BelongsToTenant's global scope reaching route-model binding, which only
     * holds because Authenticate runs before SubstituteBindings. That ordering
     * is framework-owned and invisible here, which is exactly why it needs a test.
     */
    public function test_no_hospi_route_resolves_another_tenants_record_by_id(): void
    {
        $a = $this->makeTenant('alpha-beach');
        $b = $this->makeTenant('bravo-beach');

        $waiterA = $this->makeUser('Waiter', $a);
        $managerB = $this->makeUser('Manager', $b);
        $chefB = $this->makeUser('Chef', $b, 'main_kitchen');
        $waiterB = $this->makeUser('Waiter', $b);

        $mealA = Tenant::runAs($a->id, fn () => Meal::query()->create([
            'department' => 'main_kitchen', 'name' => 'Jollof A',
            'price' => 450000, 'prep_time_minutes' => 20, 'is_available' => true,
        ]));

        $tableA = Tenant::runAs($a->id, fn () => VenueTable::query()->create([
            'name' => 'Table A1', 'is_active' => true,
        ]));

        $orderA = Tenant::runAs($a->id, fn () => Order::query()->create([
            'department' => 'main_kitchen',
            'placed_by_user_id' => $waiterA->id,
            'status' => 'pending',
            'placed_at' => now(),
            'target_minutes' => 20,
            'due_at' => now()->addMinutes(20),
            'total_amount' => 450000,
        ]));

        $transferA = Tenant::runAs($a->id, fn () => OrderTransfer::query()->create([
            'order_id' => $orderA->id, 'kind' => 'server',
            'from_user_id' => $waiterA->id, 'to_user_id' => $waiterA->id,
            'status' => 'pending',
        ]));

        $this->auth($managerB)->patchJson('/api/meals/'.$mealA->id, ['name' => 'Taken'])->assertNotFound();
        $this->auth($managerB)->postJson('/api/meals/'.$mealA->id.'/image', [
            'image_base64' => base64_encode('not-an-image'),
        ])->assertNotFound();
        $this->auth($managerB)->patchJson('/api/tables/'.$tableA->id, ['name' => 'Taken'])->assertNotFound();
        $this->auth($chefB)->postJson('/api/orders/'.$orderA->id.'/accept')->assertNotFound();
        $this->auth($managerB)->postJson('/api/orders/'.$orderA->id.'/nudge')->assertNotFound();
        $this->auth($managerB)->postJson('/api/orders/'.$orderA->id.'/expedite')->assertNotFound();
        $this->auth($managerB)->postJson('/api/orders/'.$orderA->id.'/reassign', [
            'chef_user_id' => $chefB->id,
        ])->assertNotFound();
        $this->auth($waiterB)->getJson('/api/orders/'.$orderA->id.'/transfer-targets')->assertNotFound();
        $this->auth($waiterB)->postJson('/api/orders/'.$orderA->id.'/transfer', [
            'to_user_id' => $waiterB->id,
        ])->assertNotFound();
        $this->auth($waiterB)->postJson('/api/transfers/'.$transferA->id.'/accept')->assertNotFound();
        $this->auth($waiterB)->postJson('/api/transfers/'.$transferA->id.'/decline')->assertNotFound();

        // And the record really is untouched, not merely unreported.
        $this->assertSame('Jollof A', Tenant::runAs($a->id, fn () => Meal::query()->find($mealA->id)->name));
        $this->assertSame('pending', Tenant::runAs($a->id, fn () => Order::query()->find($orderA->id)->status));
    }

    /** A meal id from another business cannot be ordered onto this business's ticket. */
    public function test_an_order_cannot_be_placed_against_another_tenants_meal(): void
    {
        $a = $this->makeTenant('alpha-beach');
        $b = $this->makeTenant('bravo-beach');

        $mealA = Tenant::runAs($a->id, fn () => Meal::query()->create([
            'department' => 'main_kitchen', 'name' => 'Jollof A',
            'price' => 450000, 'prep_time_minutes' => 20, 'is_available' => true,
        ]));

        $waiterB = $this->makeUser('Waiter', $b);

        $this->auth($waiterB)->postJson('/api/orders', [
            'department' => 'main_kitchen',
            'items' => [['meal_id' => $mealA->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->assertSame(0, Tenant::runAs($b->id, fn () => Order::query()->count()));
    }

    // ── Finding: mass assignment ───────────────────────────────────────────

    /**
     * $fillable is wide on several models (Order lists tenant_id and status;
     * SpaceBooking lists payment_status, source and receipt_path). That is only
     * a vulnerability if a route feeds request data into it, and no controller
     * in app/ calls $request->all(). This asserts the consequence rather than
     * the shape: smuggled fields are dropped by validate() and never persisted.
     */
    public function test_api_writes_cannot_smuggle_privileged_fields(): void
    {
        $a = $this->makeTenant('alpha-beach');
        $b = $this->makeTenant('bravo-beach');

        $managerB = $this->makeUser('Manager', $b);
        $waiterB = $this->makeUser('Waiter', $b);

        // A meal planted into another tenant, pre-marked unavailable.
        $meal = $this->auth($managerB)->postJson('/api/meals', [
            'department' => 'main_kitchen',
            'name' => 'Smuggled Dish',
            'price_naira' => 1000,
            'prep_time_minutes' => 15,
            'tenant_id' => $a->id,
            'sku' => 'OVERRIDDEN',
            'image_path' => '../../../etc/passwd',
        ])->assertCreated()->json('data');

        $stored = Meal::query()->withoutGlobalScope('tenant')->find($meal['id']);
        $this->assertSame((int) $b->id, (int) $stored->tenant_id, 'tenant_id was taken from the request');
        $this->assertNull($stored->image_path, 'image_path was taken from the request');
        $this->assertNull($stored->sku, 'sku was taken from the request');

        // An order pre-stamped as completed, free, and attributed to someone else.
        $order = $this->auth($waiterB)->postJson('/api/orders', [
            'department' => 'main_kitchen',
            'items' => [['meal_id' => $meal['id'], 'quantity' => 1]],
            'status' => 'completed',
            'total_amount' => 0,
            'tenant_id' => $a->id,
            'placed_by_user_id' => 999,
            'completed_at' => now()->toIso8601String(),
        ])->assertCreated()->json('data');

        $storedOrder = Order::query()->withoutGlobalScope('tenant')->find($order['id']);
        $this->assertSame('pending', $storedOrder->status);
        $this->assertSame(100000, (int) $storedOrder->total_amount);
        $this->assertSame((int) $b->id, (int) $storedOrder->tenant_id);
        $this->assertSame((int) $waiterB->id, (int) $storedOrder->placed_by_user_id);
        $this->assertNull($storedOrder->completed_at);

        // A table planted into another tenant.
        $table = $this->auth($managerB)->postJson('/api/tables', [
            'name' => 'Smuggled Table',
            'tenant_id' => $a->id,
        ])->assertCreated()->json('data');

        $storedTable = VenueTable::query()->withoutGlobalScope('tenant')->find($table['id']);
        $this->assertSame((int) $b->id, (int) $storedTable->tenant_id);
    }

    /** A guest cannot pre-pay their own website booking by asking nicely. */
    public function test_a_public_booking_cannot_set_its_own_payment_state(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Deluxe Public',
            'room_category' => 'Deluxe',
            'room_price' => 80000,
            'is_booked' => 'no',
        ]);

        $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'guest_name' => 'Chidi Guest',
            'guest_email' => 'chidi@example.com',
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_reference' => 'FORGED-REF',
        ])->assertCreated();

        $booking = Booking::query()->latest('id')->first();
        $this->assertSame('pending', $booking->status);
        $this->assertNotSame('paid', $booking->payment_status);
        $this->assertNotSame('FORGED-REF', $booking->payment_reference);
    }

    /** Same for a public space booking, whose $fillable also carries receipt_path. */
    public function test_a_public_space_booking_cannot_set_its_own_receipt_or_source(): void
    {
        $space = ServiceBooking::query()->create([
            'service_name' => 'Cabana Public',
            'service_category' => 'Cabana',
            'service_price' => 50000,
            'is_booked' => 'no',
        ]);

        $this->postJson('/api/service-bookings', [
            'service_id' => $space->id,
            'guest_name' => 'Ngozi Guest',
            'guest_email' => 'ngozi@example.com',
            'guests' => 4,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'source' => 'manual',
            'receipt_path' => 'receipts/forged.pdf',
        ])->assertCreated();

        $booking = SpaceBooking::query()->latest('id')->first();
        $this->assertSame('pending', $booking->status);
        $this->assertNotSame('paid', $booking->payment_status);
        $this->assertNotSame('manual', $booking->source);
        $this->assertNull($booking->receipt_path);
    }

    // ── Finding: token lifecycle ───────────────────────────────────────────

    /** Logout must revoke, not merely reply 200. */
    public function test_logout_actually_revokes_the_token(): void
    {
        $tenant = $this->makeTenant('alpha-beach');
        $user = $this->makeUser('Waiter', $tenant);
        $token = $user->createToken('audit')->plainTextToken;

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/me')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/auth/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/me')->assertUnauthorized();

        $this->assertSame(0, $user->tokens()->count());
    }

    // ── Fix: unauthenticated /api/* errors ─────────────────────────────────

    /**
     * An anonymous caller that does not send `Accept: application/json` used to
     * get a 500 here, because the handler redirected to route('login') and this
     * app has no route of that name — and with APP_DEBUG on, that 500 is an HTML
     * stack trace served to an anonymous caller. Must be a JSON 401.
     */
    public function test_an_unauthenticated_api_request_is_401_json_even_without_an_accept_header(): void
    {
        config(['app.debug' => true]);

        foreach (['/api/me', '/api/orders', '/api/bookings/calendar', '/api/dashboard/revenue'] as $path) {
            $this->app['auth']->forgetGuards();
            $response = $this->call('GET', $path);

            $this->assertSame(401, $response->status(), "$path did not answer 401 without an Accept header");
            $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
            $this->assertStringNotContainsString('Stack trace', $response->getContent());
            $this->assertStringNotContainsString('vendor\\laravel', $response->getContent());
        }
    }

    /** Every /api route is either public by design or refuses an anonymous caller. */
    public function test_every_api_route_is_either_public_by_design_or_401(): void
    {
        $public = [
            'api/rooms', 'api/services', 'api/health',
            'api/contact', 'api/bookings', 'api/service-bookings',
            'api/auth/login',
        ];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            $method = array_values(array_diff($route->methods(), ['HEAD']))[0];
            $path = '/'.preg_replace('/\{[^}]+\}/', '1', $uri);

            $this->app['auth']->forgetGuards();
            $status = $this->json($method, $path)->status();

            if (in_array($uri, $public, true)) {
                $this->assertContains(
                    $status,
                    [200, 201, 422, 429],
                    "$uri is in the public allowlist but answered $status",
                );

                continue;
            }

            $this->assertSame(401, $status, "$uri is reachable without a token (answered $status)");
        }
    }

    /**
     * Not a 500: a thrown exception on an /api route must be a JSON body with no
     * file path, no line number and no stack frames, whatever APP_DEBUG says in
     * production.
     */
    public function test_a_server_error_on_an_api_route_leaks_nothing_when_debug_is_off(): void
    {
        Route::middleware('api')->get('/api/_audit_boom', function (): void {
            throw new \RuntimeException('select * from users where password_hash = ?');
        });

        config(['app.debug' => false]);

        $response = $this->call('GET', '/api/_audit_boom');

        $this->assertSame(500, $response->status());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
        $this->assertSame(['message' => 'Server Error'], $response->json());
        $this->assertStringNotContainsString('password_hash', $response->getContent());
    }

    // ── Fix: every write route is throttled ────────────────────────────────

    /**
     * /api/auth/logout was the one write route with no rate limit. This asserts
     * the property rather than the one route, so a new unthrottled write fails
     * here instead of shipping.
     */
    public function test_every_api_write_route_carries_a_rate_limit(): void
    {
        $unthrottled = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $writes = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);
            if ($writes === []) {
                continue;
            }

            $hasThrottle = collect($route->gatherMiddleware())
                ->contains(fn ($m) => is_string($m)
                    && (str_contains($m, 'ThrottleRequests') || str_starts_with($m, 'throttle')));

            if (! $hasThrottle) {
                $unthrottled[] = implode('|', $writes).' '.$route->uri();
            }
        }

        $this->assertSame([], $unthrottled, 'Unthrottled API write route(s): '.implode(', ', $unthrottled));
    }
}
