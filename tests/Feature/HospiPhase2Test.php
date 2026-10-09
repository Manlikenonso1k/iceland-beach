<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Notifications\OrderOverdueNotification;
use App\Notifications\OrderPlacedNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 2 stop-gate (HOSPI_SALES_BRIEF §5.6). Every proof required before any
 * other department may be built lives here.
 */
class HospiPhase2Test extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug).' Beach',
            'slug' => $slug,
            'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen', 'barbecue', 'ice_cream'],
            'timezone' => 'Africa/Lagos',
            'is_active' => true,
        ]);
    }

    private function makeUser(string $role, Tenant $tenant, ?string $department = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($role, 'web');

        $user = User::query()->create([
            'name' => $role.' '.$tenant->slug,
            'email' => strtolower($role).'-'.$tenant->slug.'@hospi.test',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'department' => $department,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function auth(User $user): self
    {
        // A single test makes several in-process requests as different users;
        // the auth guard memoizes the first-resolved user, so flush it before
        // each so the new Bearer token is honoured. (A non-issue in production,
        // where every request is a fresh process.)
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$this->token($user));
    }

    private function makeMeal(Tenant $tenant, string $department, int $prep, int $price = 450000): Meal
    {
        return Tenant::runAs($tenant->id, fn () => Meal::query()->create([
            'department' => $department,
            'name' => 'Dish '.uniqid(),
            'category' => 'Test',
            'price' => $price,
            'prep_time_minutes' => $prep,
            'is_available' => true,
        ]));
    }

    private function placeOrder(User $waitress, Tenant $tenant, string $department, array $mealIds): Order
    {
        return Tenant::runAs($tenant->id, fn () => app(OrderService::class)->place(
            $waitress,
            $department,
            array_map(fn ($id) => ['meal_id' => $id, 'quantity' => 1], $mealIds),
        ));
    }

    // 1 ────────────────────────────────────────────────────────────────────
    public function test_tenant_a_cannot_read_write_or_enumerate_tenant_b_order(): void
    {
        Notification::fake();

        $a = $this->makeTenant('alpha');
        $b = $this->makeTenant('beta');
        $aManager = $this->makeUser('Manager', $a);
        $bWaitress = $this->makeUser('Waiter', $b);
        $bMeal = $this->makeMeal($b, 'main_kitchen', 20);
        $bOrder = $this->placeOrder($bWaitress, $b, 'main_kitchen', [$bMeal->id]);

        // Enumerate: A's board and list never contain B's order.
        $this->auth($aManager)->getJson('/api/orders/board')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->auth($aManager)->getJson('/api/orders')
            ->assertOk()->assertJsonCount(0, 'data');

        // Read/write a known B id: scoped route binding yields 404, not access.
        $this->auth($aManager)->postJson("/api/orders/{$bOrder->id}/accept")
            ->assertNotFound();

        $this->assertSame('pending', $bOrder->fresh()->status);
    }

    // 2 ────────────────────────────────────────────────────────────────────
    public function test_waitress_is_refused_on_every_chef_and_manager_endpoint(): void
    {
        Notification::fake();

        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $chef = $this->makeUser('Chef', $t, 'main_kitchen');
        $meal = $this->makeMeal($t, 'main_kitchen', 20);
        $order = $this->placeOrder($waitress, $t, 'main_kitchen', [$meal->id]);

        $this->auth($waitress)->getJson('/api/orders/board')->assertForbidden();
        $this->auth($waitress)->postJson("/api/orders/{$order->id}/accept")->assertForbidden();
        $this->auth($waitress)->postJson("/api/orders/{$order->id}/decline", ['reason' => 'x'])->assertForbidden();
        $this->auth($waitress)->postJson("/api/orders/{$order->id}/ready")->assertForbidden();

        $this->assertSame('pending', $order->fresh()->status);
    }

    // 3 ────────────────────────────────────────────────────────────────────
    public function test_chef_cannot_see_another_departments_queue(): void
    {
        Notification::fake();

        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $mainChef = $this->makeUser('Chef', $t, 'main_kitchen');
        $mainMeal = $this->makeMeal($t, 'main_kitchen', 20);
        $bbqMeal = $this->makeMeal($t, 'barbecue', 20);

        $this->placeOrder($waitress, $t, 'main_kitchen', [$mainMeal->id]);
        $this->placeOrder($waitress, $t, 'barbecue', [$bbqMeal->id]);

        $response = $this->auth($mainChef)->getJson('/api/orders')->assertOk();
        $departments = collect($response->json('data'))->pluck('department')->unique()->values()->all();

        $this->assertSame(['main_kitchen'], $departments);
    }

    // 4 ────────────────────────────────────────────────────────────────────
    public function test_order_is_amber_before_due_at_and_red_after_from_due_at(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-09-30 12:00:00');

        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $manager = $this->makeUser('Manager', $t);
        $meal = $this->makeMeal($t, 'main_kitchen', 5); // 5-minute target
        $order = $this->placeOrder($waitress, $t, 'main_kitchen', [$meal->id]);

        // 2 minutes in: still within the 5-minute SLA → AMBER.
        Carbon::setTestNow('2026-09-30 12:02:00');
        $this->assertSame('amber', $this->boardColour($manager, $order->id));

        // 6 minutes in: past due_at → RED, computed from due_at not client input.
        Carbon::setTestNow('2026-09-30 12:06:00');
        $this->assertSame('red', $this->boardColour($manager, $order->id));

        Carbon::setTestNow();
    }

    private function boardColour(User $manager, int $orderId): string
    {
        $data = $this->auth($manager)->getJson('/api/orders/board')->assertOk()->json('data');

        return collect($data)->firstWhere('id', $orderId)['status_colour'];
    }

    // 5 ────────────────────────────────────────────────────────────────────
    public function test_overdue_job_pushes_to_manager_exactly_once_with_no_app_open(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-09-30 12:00:00');

        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $manager = $this->makeUser('Manager', $t);
        $meal = $this->makeMeal($t, 'main_kitchen', 5);
        $order = $this->placeOrder($waitress, $t, 'main_kitchen', [$meal->id]);

        // Move past due_at, then run the scheduled sweep twice (no app involved).
        Carbon::setTestNow('2026-09-30 12:10:00');
        $this->artisan('orders:flag-overdue')->assertSuccessful();
        $this->artisan('orders:flag-overdue')->assertSuccessful();

        Notification::assertSentToTimes($manager, OrderOverdueNotification::class, 1);
        $this->assertNotNull($order->fresh()->notified_overdue_at);

        Carbon::setTestNow();
    }

    // 6 ────────────────────────────────────────────────────────────────────
    public function test_empty_decline_reason_is_rejected_and_nothing_is_written(): void
    {
        Notification::fake();

        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $chef = $this->makeUser('Chef', $t, 'main_kitchen');
        $meal = $this->makeMeal($t, 'main_kitchen', 20);
        $order = $this->placeOrder($waitress, $t, 'main_kitchen', [$meal->id]);

        $this->auth($chef)->postJson("/api/orders/{$order->id}/decline", ['reason' => '   '])
            ->assertStatus(422);

        $fresh = $order->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertNull($fresh->declined_at);
        $this->assertNull($fresh->decline_reason);
    }

    // 7 ────────────────────────────────────────────────────────────────────
    public function test_raising_a_meals_prep_time_does_not_change_an_existing_order(): void
    {
        Notification::fake();

        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $meal = $this->makeMeal($t, 'main_kitchen', 25);
        $order = $this->placeOrder($waitress, $t, 'main_kitchen', [$meal->id]);

        $originalTarget = $order->target_minutes;
        $originalDue = $order->due_at->toIso8601String();

        Tenant::runAs($t->id, fn () => $meal->update(['prep_time_minutes' => 90]));

        $fresh = $order->fresh();
        $this->assertSame($originalTarget, $fresh->target_minutes);
        $this->assertSame($originalDue, $fresh->due_at->toIso8601String());
        $this->assertSame(25, $order->items()->first()->prep_time_minutes_snapshot);
    }

    // Happy path ─────────────────────────────────────────────────────────────
    public function test_full_lifecycle_places_accepts_readies_completes_and_notifies(): void
    {
        Notification::fake();

        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $chef = $this->makeUser('Chef', $t, 'main_kitchen');
        $manager = $this->makeUser('Manager', $t);
        $meal = $this->makeMeal($t, 'main_kitchen', 30);

        $order = $this->auth($waitress)->postJson('/api/orders', [
            'department' => 'main_kitchen',
            'table_id' => 4,
            'items' => [['meal_id' => $meal->id, 'quantity' => 2]],
        ])->assertCreated()->assertJsonPath('data.status_colour', 'amber')->json('data');

        // New-order alert reaches both the kitchen and the manager.
        Notification::assertSentTo($chef, OrderPlacedNotification::class);
        Notification::assertSentTo($manager, OrderPlacedNotification::class);

        $id = $order['id'];
        $this->auth($chef)->postJson("/api/orders/{$id}/accept")->assertOk()->assertJsonPath('data.status', 'accepted');
        $this->auth($chef)->postJson("/api/orders/{$id}/ready")->assertOk()->assertJsonPath('data.status', 'ready');
        $this->auth($manager)->postJson("/api/orders/{$id}/complete")->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.status_colour', 'green');

        $this->assertSame(900000, Order::query()->find($id)->total_amount); // 2 × ₦4,500
    }
}
