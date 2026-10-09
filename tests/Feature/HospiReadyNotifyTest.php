<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OrderReadyNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HospiReadyNotifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_ready_notifies_the_waitress_who_placed_it(): void
    {
        Notification::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Alpha', 'slug' => 'alpha', 'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['Waiter', 'Chef'] as $r) {
            Role::findOrCreate($r, 'web');
        }
        $waitress = User::query()->create(['name' => 'Ada', 'email' => 'ada@hospi.test', 'password' => Hash::make('x'), 'tenant_id' => $tenant->id]);
        $waitress->assignRole('Waiter');
        $chef = User::query()->create(['name' => 'Chef', 'email' => 'chef@hospi.test', 'password' => Hash::make('x'), 'tenant_id' => $tenant->id, 'department' => 'main_kitchen']);
        $chef->assignRole('Chef');

        Tenant::runAs($tenant->id, function () use ($waitress, $chef) {
            $meal = Meal::query()->create(['department' => 'main_kitchen', 'name' => 'Jollof', 'price' => 450000, 'prep_time_minutes' => 20, 'is_available' => true]);
            $svc = app(OrderService::class);
            $order = $svc->place($waitress, 'main_kitchen', [['meal_id' => $meal->id, 'quantity' => 1]]);
            $svc->accept($order, $chef);
            $svc->ready($order->fresh(), $chef);
        });

        Notification::assertSentTo($waitress, OrderReadyNotification::class);
    }
}
