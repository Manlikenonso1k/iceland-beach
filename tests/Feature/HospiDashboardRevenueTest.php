<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HospiDashboardRevenueTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Alpha', 'slug' => 'alpha', 'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen', 'barbecue'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
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

    public function test_ceo_sees_department_revenue_and_delay_accountability(): void
    {
        $t = $this->tenant();
        $ceo = $this->user('CEO', $t);
        $waitress = $this->user('Waiter', $t);
        $mainChef = $this->user('Chef', $t, 'main_kitchen');
        $bbqChef = $this->user('Chef', $t, 'barbecue');

        Tenant::runAs($t->id, function () use ($waitress, $mainChef, $bbqChef) {
            // On-time completed main-kitchen ticket.
            Order::query()->create([
                'department' => 'main_kitchen', 'placed_by_user_id' => $waitress->id, 'chef_user_id' => $mainChef->id,
                'status' => 'completed', 'placed_at' => now()->subMinutes(30), 'target_minutes' => 15,
                'due_at' => now()->subMinutes(15), 'ready_at' => now()->subMinutes(20),
                'completed_at' => now()->subMinutes(18), 'total_amount' => 500000,
            ]);
            // Completed LATE main-kitchen ticket: chef cook-delay + waitress slow pickup (10m).
            Order::query()->create([
                'department' => 'main_kitchen', 'placed_by_user_id' => $waitress->id, 'chef_user_id' => $mainChef->id,
                'status' => 'completed', 'placed_at' => now()->subMinutes(60), 'target_minutes' => 20,
                'due_at' => now()->subMinutes(40), 'ready_at' => now()->subMinutes(30),
                'completed_at' => now()->subMinutes(20), 'total_amount' => 300000,
            ]);
            // Open + overdue barbecue ticket: bbq chef cook-delay, counts as overdue now.
            Order::query()->create([
                'department' => 'barbecue', 'placed_by_user_id' => $waitress->id, 'chef_user_id' => $bbqChef->id,
                'status' => 'accepted', 'placed_at' => now()->subMinutes(50), 'target_minutes' => 40,
                'due_at' => now()->subMinutes(10), 'total_amount' => 0,
            ]);
        });

        $res = $this->auth($ceo)->getJson('/api/dashboard/revenue?period=today')->assertOk();

        $res->assertJsonPath('total_gross', 800000)
            ->assertJsonPath('orders_fulfilled', 2)
            ->assertJsonPath('avg_ticket', 400000)
            ->assertJsonPath('overdue_count', 1);

        $departments = collect($res->json('departments'))->keyBy('department');
        $this->assertSame(800000, $departments['main_kitchen']['revenue']);
        $this->assertSame(2, $departments['main_kitchen']['tickets']);
        $this->assertSame(0, $departments['main_kitchen']['overdue']);
        $this->assertSame(0, $departments['barbecue']['revenue']);
        $this->assertSame(1, $departments['barbecue']['overdue']);

        $chefDelays = collect($res->json('delays.chefs'))->keyBy('name');
        $this->assertSame(1, $chefDelays[$mainChef->name]['count']); // one late completion
        $this->assertSame(1, $chefDelays[$bbqChef->name]['count']);  // one currently overdue

        $waitDelays = collect($res->json('delays.waitresses'))->keyBy('name');
        $this->assertSame(1, $waitDelays[$waitress->name]['count']); // the 10-minute pickup
    }

    public function test_waiter_cannot_see_the_dashboard(): void
    {
        $t = $this->tenant();
        $waiter = $this->user('Waiter', $t);

        $this->auth($waiter)->getJson('/api/dashboard/revenue')->assertForbidden();
    }

    public function test_ceo_can_create_a_waitress_account(): void
    {
        $t = $this->tenant();
        $ceo = $this->user('CEO', $t);
        Role::findOrCreate('Waiter', 'web');

        $this->auth($ceo)->postJson('/api/staff', [
            'name' => 'New Waitress', 'phone' => '08030000001', 'password' => 'password', 'role' => 'Waiter',
        ])->assertCreated()->assertJsonPath('data.roles.0', 'Waiter');
    }
}
