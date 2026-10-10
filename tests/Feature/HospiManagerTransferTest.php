<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * A manager/CEO can hand off any order (to a waitress, or a same-station chef);
 * the recipient still accepts/declines like a peer transfer.
 */
class HospiManagerTransferTest extends TestCase
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
            'enabled_departments' => ['main_kitchen'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
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

    private function order(User $waitress, Tenant $t): Order
    {
        return Tenant::runAs($t->id, function () use ($waitress) {
            $meal = Meal::query()->create(['department' => 'main_kitchen', 'name' => 'D'.uniqid(), 'price' => 450000, 'prep_time_minutes' => 20, 'is_available' => true]);

            return app(OrderService::class)->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 1]]);
        });
    }

    public function test_manager_can_transfer_an_order_to_another_waitress(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        $w1 = $this->user('Waiter', $t);
        $w2 = $this->user('Waiter', $t);
        $order = $this->order($w1, $t);

        // Manager sees both waitresses as targets and sends it to w2.
        $this->auth($manager)->getJson("/api/orders/{$order->id}/transfer-targets")
            ->assertOk()->assertJsonCount(2, 'data');

        $this->auth($manager)->postJson("/api/orders/{$order->id}/transfer", ['to_user_id' => $w2->id])
            ->assertCreated()->assertJsonPath('data.kind', 'server');

        // w2 accepts; ownership moves to her.
        $transferId = \App\Models\OrderTransfer::query()->where('order_id', $order->id)->value('id');
        $this->auth($w2)->postJson("/api/transfers/{$transferId}/accept")->assertOk();

        $this->assertSame($w2->id, $order->fresh()->placed_by_user_id);
    }

    public function test_manager_transfer_to_chef_uses_chef_kind(): void
    {
        $t = $this->tenant();
        $manager = $this->user('Manager', $t);
        $w1 = $this->user('Waiter', $t);
        $chef = $this->user('Chef', $t, 'main_kitchen');
        $order = $this->order($w1, $t);

        $this->auth($manager)->postJson("/api/orders/{$order->id}/transfer", ['to_user_id' => $chef->id])
            ->assertCreated()->assertJsonPath('data.kind', 'chef');
    }

    public function test_ceo_can_initiate_a_transfer(): void
    {
        $t = $this->tenant();
        $ceo = $this->user('CEO', $t);
        $w1 = $this->user('Waiter', $t);
        $w2 = $this->user('Waiter', $t);
        $order = $this->order($w1, $t);

        $this->auth($ceo)->postJson("/api/orders/{$order->id}/transfer", ['to_user_id' => $w2->id])
            ->assertCreated()->assertJsonPath('data.kind', 'server');
    }
}
