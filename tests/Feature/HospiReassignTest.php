<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OrderAssignedNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HospiReassignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Alpha', 'slug' => 'alpha', 'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen', 'grill_kitchen'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
        ]);
    }

    private function user(string $role, Tenant $t, ?string $dept = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($role, 'web');
        $u = User::query()->create([
            'name' => $role.'-'.uniqid(), 'email' => strtolower($role).'-'.uniqid().'@hospi.test',
            'password' => Hash::make('password'), 'tenant_id' => $t->id, 'department' => $dept,
        ]);
        $u->assignRole($role);

        return $u;
    }

    private function auth(User $u): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$u->createToken('t')->plainTextToken);
    }

    private function order(User $waitress, Tenant $t, string $dept = 'main_kitchen'): Order
    {
        return Tenant::runAs($t->id, function () use ($waitress, $dept) {
            $meal = Meal::query()->create(['department' => $dept, 'name' => 'D'.uniqid(), 'price' => 450000, 'prep_time_minutes' => 20, 'is_available' => true]);

            return app(OrderService::class)->place($waitress, $dept, [['meal_id' => $meal->id, 'quantity' => 1]]);
        });
    }

    public function test_manager_reassigns_server_and_chef(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        $w1 = $this->user('Waiter', $t);
        $w2 = $this->user('Waiter', $t);
        $chef = $this->user('Chef', $t, 'main_kitchen');
        $order = $this->order($w1, $t, 'main_kitchen');

        $this->auth($manager)->postJson("/api/orders/{$order->id}/reassign", [
            'server_user_id' => $w2->id, 'chef_user_id' => $chef->id,
        ])->assertOk();

        $fresh = $order->fresh();
        $this->assertSame($w2->id, $fresh->placed_by_user_id);
        $this->assertSame($chef->id, $fresh->chef_user_id);
        Notification::assertSentTo($w2, OrderAssignedNotification::class);
        Notification::assertSentTo($chef, OrderAssignedNotification::class);
    }

    public function test_chef_must_be_on_the_orders_station(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        $w1 = $this->user('Waiter', $t);
        $grillChef = $this->user('Chef', $t, 'grill_kitchen');
        $order = $this->order($w1, $t, 'main_kitchen');

        $this->auth($manager)->postJson("/api/orders/{$order->id}/reassign", [
            'chef_user_id' => $grillChef->id,
        ])->assertStatus(422);
    }

    public function test_waiter_cannot_reassign(): void
    {
        $t = $this->tenant();
        $w1 = $this->user('Waiter', $t);
        $w2 = $this->user('Waiter', $t);
        $order = $this->order($w1, $t);

        $this->auth($w1)->postJson("/api/orders/{$order->id}/reassign", ['server_user_id' => $w2->id])->assertForbidden();
    }

    public function test_reassign_needs_a_target(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        $w1 = $this->user('Waiter', $t);
        $order = $this->order($w1, $t);

        $this->auth($manager)->postJson("/api/orders/{$order->id}/reassign", [])->assertStatus(422);
    }
}
