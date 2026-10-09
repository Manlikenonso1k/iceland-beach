<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OrderExpeditedNotification;
use App\Notifications\OrderNudgeNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Additive manager escalation endpoints (Order Board): nudge, expedite, and the
 * notification unread-count that backs the header bell.
 */
class HospiNudgeExpediteTest extends TestCase
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

    private function auth(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken);
    }

    private function makeOrder(User $waitress, Tenant $tenant): Order
    {
        $meal = Tenant::runAs($tenant->id, fn () => Meal::query()->create([
            'department' => 'main_kitchen',
            'name' => 'Dish '.uniqid(),
            'price' => 450000,
            'prep_time_minutes' => 20,
            'is_available' => true,
        ]));

        return Tenant::runAs($tenant->id, fn () => app(OrderService::class)->place(
            $waitress,
            'main_kitchen',
            [['meal_id' => $meal->id, 'quantity' => 1]],
        ));
    }

    public function test_manager_nudge_stamps_and_notifies_the_kitchen(): void
    {
        Notification::fake();
        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $manager = $this->makeUser('Manager', $t);
        $chef = $this->makeUser('Chef', $t, 'main_kitchen');
        $order = $this->makeOrder($waitress, $t);

        $this->auth($manager)->postJson("/api/orders/{$order->id}/nudge")
            ->assertOk()->assertJsonPath('data.id', $order->id);

        $this->assertNotNull($order->fresh()->last_nudged_at);
        Notification::assertSentTo($chef, OrderNudgeNotification::class);
    }

    public function test_manager_expedite_stamps_and_notifies(): void
    {
        Notification::fake();
        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $manager = $this->makeUser('Manager', $t);
        $chef = $this->makeUser('Chef', $t, 'main_kitchen');
        $order = $this->makeOrder($waitress, $t);

        $this->auth($manager)->postJson("/api/orders/{$order->id}/expedite")->assertOk();

        $this->assertNotNull($order->fresh()->expedited_at);
        Notification::assertSentTo($chef, OrderExpeditedNotification::class);
    }

    public function test_waitress_cannot_nudge_or_expedite(): void
    {
        Notification::fake();
        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $order = $this->makeOrder($waitress, $t);

        $this->auth($waitress)->postJson("/api/orders/{$order->id}/nudge")->assertForbidden();
        $this->auth($waitress)->postJson("/api/orders/{$order->id}/expedite")->assertForbidden();
        $this->assertNull($order->fresh()->last_nudged_at);
    }

    public function test_nudge_is_tenant_scoped(): void
    {
        Notification::fake();
        $a = $this->makeTenant('alpha');
        $b = $this->makeTenant('beta');
        $aManager = $this->makeUser('Manager', $a);
        $bWaitress = $this->makeUser('Waiter', $b);
        $bOrder = $this->makeOrder($bWaitress, $b);

        $this->auth($aManager)->postJson("/api/orders/{$bOrder->id}/nudge")->assertNotFound();
        $this->assertNull($bOrder->fresh()->last_nudged_at);
    }

    public function test_cannot_nudge_a_completed_order(): void
    {
        Notification::fake();
        $t = $this->makeTenant('alpha');
        $waitress = $this->makeUser('Waiter', $t);
        $manager = $this->makeUser('Manager', $t);
        $order = $this->makeOrder($waitress, $t);
        $order->update(['status' => 'completed', 'completed_at' => now()]);

        $this->auth($manager)->postJson("/api/orders/{$order->id}/nudge")->assertStatus(422);
    }

    public function test_unread_count_reflects_database_notifications(): void
    {
        Notification::fake(); // placing the order would notify; fake keeps count at 0
        $t = $this->makeTenant('alpha');
        $manager = $this->makeUser('Manager', $t);

        $this->auth($manager)->getJson('/api/notifications/unread-count')
            ->assertOk()->assertJsonPath('count', 0);
    }
}
