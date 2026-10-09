<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Shift;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HospiShiftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug), 'slug' => $slug, 'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen', 'barbecue'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
        ]);
    }

    private function user(string $role, Tenant $t, ?string $dept = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($role, 'web');
        $u = User::query()->create([
            'name' => $role.'-'.uniqid(), 'email' => strtolower($role).'-'.$t->slug.'-'.uniqid().'@hospi.test',
            'password' => Hash::make('password'), 'tenant_id' => $t->id, 'department' => $dept,
        ]);
        $u->assignRole($role);

        return $u;
    }

    private function meal(Tenant $t, string $dept): Meal
    {
        return Tenant::runAs($t->id, fn () => Meal::query()->create([
            'department' => $dept, 'name' => 'Dish '.uniqid(), 'price' => 450000,
            'prep_time_minutes' => 20, 'is_available' => true,
        ]));
    }

    public function test_order_is_attributed_to_the_chef_on_shift(): void
    {
        $t = $this->tenant('alpha');
        $waitress = $this->user('Waiter', $t);
        $chef = $this->user('Chef', $t, 'main_kitchen');
        $meal = $this->meal($t, 'main_kitchen');

        Tenant::runAs($t->id, function () use ($chef, $waitress, $meal): void {
            app(ShiftService::class)->start($chef); // defaults to chef's department
            $order = app(OrderService::class)->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 1]]);
            $this->assertSame($chef->id, $order->chef_user_id);
        });
    }

    public function test_most_recently_started_shift_wins(): void
    {
        $t = $this->tenant('alpha');
        $waitress = $this->user('Waiter', $t);
        $chefA = $this->user('Chef', $t, 'main_kitchen');
        $chefB = $this->user('Chef', $t, 'main_kitchen');
        $meal = $this->meal($t, 'main_kitchen');

        Tenant::runAs($t->id, function () use ($chefA, $chefB, $waitress, $meal): void {
            app(ShiftService::class)->start($chefA);
            app(ShiftService::class)->start($chefB); // later → wins
            $order = app(OrderService::class)->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 1]]);
            $this->assertSame($chefB->id, $order->chef_user_id);
        });
    }

    public function test_no_shift_leaves_chef_null_until_accept_pins_it(): void
    {
        $t = $this->tenant('alpha');
        $waitress = $this->user('Waiter', $t);
        $chef = $this->user('Chef', $t, 'main_kitchen');
        $meal = $this->meal($t, 'main_kitchen');

        Tenant::runAs($t->id, function () use ($chef, $waitress, $meal): void {
            $order = app(OrderService::class)->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 1]]);
            $this->assertNull($order->chef_user_id);

            app(OrderService::class)->accept($order, $chef);
            $this->assertSame($chef->id, $order->fresh()->chef_user_id);
        });
    }

    public function test_ending_a_shift_stops_attribution(): void
    {
        $t = $this->tenant('alpha');
        $waitress = $this->user('Waiter', $t);
        $chef = $this->user('Chef', $t, 'main_kitchen');
        $meal = $this->meal($t, 'main_kitchen');

        Tenant::runAs($t->id, function () use ($chef, $waitress, $meal): void {
            $svc = app(ShiftService::class);
            $svc->start($chef);
            $svc->end($chef);
            $order = app(OrderService::class)->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 1]]);
            $this->assertNull($order->chef_user_id);
        });
    }

    public function test_shift_only_attributes_its_own_department(): void
    {
        $t = $this->tenant('alpha');
        $waitress = $this->user('Waiter', $t);
        $chef = $this->user('Chef', $t, 'main_kitchen');
        $bbqMeal = $this->meal($t, 'barbecue');

        Tenant::runAs($t->id, function () use ($chef, $waitress, $bbqMeal): void {
            app(ShiftService::class)->start($chef); // main_kitchen shift
            $order = app(OrderService::class)->place($waitress, 'barbecue', [['meal_id' => $bbqMeal->id, 'quantity' => 1]]);
            $this->assertNull($order->chef_user_id); // barbecue has no chef on shift
        });
    }
}
