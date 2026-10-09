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

class HospiDuplicateGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function waitress(Tenant $t): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Waiter', 'web');
        $u = User::query()->create(['name' => 'Ada', 'email' => 'ada@hospi.test', 'password' => Hash::make('x'), 'tenant_id' => $t->id]);
        $u->assignRole('Waiter');

        return $u;
    }

    public function test_identical_rapid_order_is_not_duplicated(): void
    {
        $t = Tenant::query()->create([
            'name' => 'Alpha', 'slug' => 'alpha', 'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
        ]);

        Tenant::runAs($t->id, function () use ($t) {
            $waitress = $this->waitress($t);
            $meal = Meal::query()->create(['department' => 'main_kitchen', 'name' => 'Jollof', 'price' => 450000, 'prep_time_minutes' => 20, 'is_available' => true]);
            $svc = app(OrderService::class);

            $first = $svc->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 2]]);
            $second = $svc->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 2]]);

            $this->assertSame($first->id, $second->id); // same order returned
            $this->assertSame(1, Order::query()->count()); // only one row created
        });
    }

    public function test_a_different_order_still_creates_a_new_one(): void
    {
        $t = Tenant::query()->create([
            'name' => 'Beta', 'slug' => 'beta', 'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
        ]);

        Tenant::runAs($t->id, function () use ($t) {
            $waitress = $this->waitress($t);
            $meal = Meal::query()->create(['department' => 'main_kitchen', 'name' => 'Jollof', 'price' => 450000, 'prep_time_minutes' => 20, 'is_available' => true]);
            $svc = app(OrderService::class);

            $svc->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 2]]);
            $svc->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 3]]); // different qty

            $this->assertSame(2, Order::query()->count());
        });
    }
}
