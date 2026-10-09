<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VenueTable;
use App\Notifications\OrderTransferNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HospiStaffTablesTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function tenant(string $slug = 'alpha'): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug), 'slug' => $slug, 'primary_colour' => '#002F61',
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
            $meal = Meal::query()->create(['department' => $dept, 'name' => 'Dish '.uniqid(), 'price' => 450000, 'prep_time_minutes' => 20, 'is_available' => true]);

            return app(OrderService::class)->place($waitress, $dept, [['meal_id' => $meal->id, 'quantity' => 1]]);
        });
    }

    // ---- Staff ------------------------------------------------------------
    public function test_manager_creates_a_waitress_and_a_chef(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        foreach (['Waiter', 'Chef'] as $r) {
            Role::findOrCreate($r, 'web');
        }

        $this->auth($manager)->postJson('/api/staff', [
            'name' => 'New Waitress', 'phone' => '0810111', 'password' => 'secret1', 'role' => 'Waiter',
        ])->assertCreated()->assertJsonPath('data.roles.0', 'Waiter');

        $this->auth($manager)->postJson('/api/staff', [
            'name' => 'New Chef', 'email' => 'chefx@hospi.test', 'password' => 'secret1', 'role' => 'Chef', 'department' => 'grill_kitchen',
        ])->assertCreated()->assertJsonPath('data.department', 'grill_kitchen');
    }

    public function test_chef_without_department_is_rejected(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        Role::findOrCreate('Chef', 'web');

        $this->auth($manager)->postJson('/api/staff', [
            'name' => 'C', 'email' => 'c@hospi.test', 'password' => 'secret1', 'role' => 'Chef',
        ])->assertStatus(422);
    }

    public function test_manager_cannot_create_a_manager_but_ceo_can(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        $ceo = $this->user('CEO', $t);

        $this->auth($manager)->postJson('/api/staff', [
            'name' => 'M', 'email' => 'm@hospi.test', 'password' => 'secret1', 'role' => 'Manager',
        ])->assertStatus(422); // Manager not in manager's assignable roles

        $this->auth($ceo)->postJson('/api/staff', [
            'name' => 'M2', 'email' => 'm2@hospi.test', 'password' => 'secret1', 'role' => 'Manager',
        ])->assertCreated();
    }

    public function test_waiter_cannot_reach_staff_creation(): void
    {
        $t = $this->tenant();
        $waiter = $this->user('Waiter', $t);
        $this->auth($waiter)->postJson('/api/staff', ['name' => 'x', 'password' => 'secret1', 'role' => 'Waiter', 'phone' => '1'])->assertForbidden();
    }

    // ---- Tables -----------------------------------------------------------
    public function test_manager_creates_tables_waiter_lists_but_cannot_create(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        $waiter = $this->user('Waiter', $t);

        $this->auth($manager)->postJson('/api/tables', ['name' => 'Cabana 04', 'zone' => 'Poolside'])->assertCreated();
        $this->auth($waiter)->getJson('/api/tables')->assertOk()->assertJsonPath('data.0.name', 'Cabana 04');
        $this->auth($waiter)->postJson('/api/tables', ['name' => 'Cabana 05'])->assertForbidden();
    }

    // ---- Transfers --------------------------------------------------------
    public function test_waitress_transfers_an_order_and_recipient_accepts(): void
    {
        $t = $this->tenant();
        $a = $this->user('Waiter', $t);
        $b = $this->user('Waiter', $t);
        $order = $this->order($a, $t);

        $this->auth($a)->postJson("/api/orders/{$order->id}/transfer", ['to_user_id' => $b->id])
            ->assertCreated()->assertJsonPath('data.kind', 'server');
        Notification::assertSentTo($b, OrderTransferNotification::class);

        $transfer = OrderTransfer::query()->first();
        $this->auth($b)->getJson('/api/transfers')->assertOk()->assertJsonCount(1, 'data');
        $this->auth($b)->postJson("/api/transfers/{$transfer->id}/accept")->assertOk();

        $this->assertSame($b->id, $order->fresh()->placed_by_user_id);
    }

    public function test_cannot_transfer_an_order_you_did_not_place(): void
    {
        $t = $this->tenant();
        $a = $this->user('Waiter', $t);
        $b = $this->user('Waiter', $t);
        $order = $this->order($a, $t);

        $this->auth($b)->postJson("/api/orders/{$order->id}/transfer", ['to_user_id' => $a->id])->assertForbidden();
    }

    public function test_recipient_can_decline_and_order_is_unchanged(): void
    {
        $t = $this->tenant();
        $a = $this->user('Waiter', $t);
        $b = $this->user('Waiter', $t);
        $order = $this->order($a, $t);

        $this->auth($a)->postJson("/api/orders/{$order->id}/transfer", ['to_user_id' => $b->id])->assertCreated();
        $transfer = OrderTransfer::query()->first();
        $this->auth($b)->postJson("/api/transfers/{$transfer->id}/decline")->assertOk();

        $this->assertSame('declined', $transfer->fresh()->status);
        $this->assertSame($a->id, $order->fresh()->placed_by_user_id);
    }

    public function test_chef_transfers_to_a_chef_on_the_same_station(): void
    {
        $t = $this->tenant();
        $waitress = $this->user('Waiter', $t);
        $chefA = $this->user('Chef', $t, 'main_kitchen');
        $chefB = $this->user('Chef', $t, 'main_kitchen');
        $order = $this->order($waitress, $t, 'main_kitchen');

        $this->auth($chefA)->postJson("/api/orders/{$order->id}/transfer", ['to_user_id' => $chefB->id])
            ->assertCreated()->assertJsonPath('data.kind', 'chef');
        $transfer = OrderTransfer::query()->first();
        $this->auth($chefB)->postJson("/api/transfers/{$transfer->id}/accept")->assertOk();

        $this->assertSame($chefB->id, $order->fresh()->chef_user_id);
    }

    public function test_server_transfer_to_a_non_waitress_is_rejected(): void
    {
        $t = $this->tenant();
        $a = $this->user('Waiter', $t);
        $chef = $this->user('Chef', $t, 'main_kitchen');
        $order = $this->order($a, $t);

        $this->auth($a)->postJson("/api/orders/{$order->id}/transfer", ['to_user_id' => $chef->id])->assertStatus(422);
    }
}
