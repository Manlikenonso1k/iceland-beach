<?php

namespace Database\Seeders;

use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VenueTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Fills the demo tenant with a realistic spread of orders so every screen has
 * something to show: the board (green/amber/red), the kitchen queue, waiters'
 * "My Orders", and the CEO/Manager revenue dashboard (department earnings plus
 * chef cook-delays and waitress slow-pickups).
 *
 * Idempotent: it clears the demo tenant's existing orders first, so re-running
 * gives a clean, freshly-timed dataset.
 *
 *   php artisan db:seed --class=Database\\Seeders\\HospiDemoOrdersSeeder
 */
class HospiDemoOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'iceland-beach')->first();
        if (! $tenant) {
            $this->command?->warn('No iceland-beach demo tenant — run HospiDemoSeeder first.');

            return;
        }

        Tenant::runAs($tenant->id, function (): void {
            // Clean slate so re-runs don't pile up duplicate tickets.
            $ids = Order::query()->pluck('id');
            OrderItem::query()->whereIn('order_id', $ids)->delete();
            Order::query()->delete();

            $meals = Meal::query()->get()->keyBy('name');
            $tables = VenueTable::query()->get()->keyBy('name');

            $u = fn (string $email) => User::query()->where('tenant_id', $this->tenantId())->where('email', $email)->first();

            $waiter = $u('waiter@hospi.test');
            $waiter2 = $u('waiter2@hospi.test');
            $chefMain = $u('chef@hospi.test');
            $chefGrill = $u('grillchef@hospi.test');
            $chefBbq = $u('bbqchef@hospi.test');
            $chefIce = $u('icecreamchef@hospi.test');

            $tid = fn (string $name) => $tables->get($name)?->id;

            // dept, waitress, chef, status, placedAgo, target, acceptedAgo, readyAgo, completedAgo, items[[name,qty]], table
            $orders = [
                // --- Today: completed (revenue), on time and late ---
                ['main_kitchen', $waiter, $chefMain, 'completed', 40, 25, 38, 20, 18, [['Jollof Rice & Chicken', 2]], 'Cabana 04'],
                ['main_kitchen', $waiter2, $chefMain, 'completed', 70, 25, 68, 40, 30, [['Fried Rice & Turkey', 1], ['Vanilla Scoop', 2]], 'Room 108'], // late + slow pickup
                ['ice_cream', $waiter, $chefIce, 'completed', 180, 5, 179, 178, 176, [['Vanilla Scoop', 3]], 'Sunbed 07'],
                ['grill_kitchen', $waiter2, $chefGrill, 'completed', 200, 35, 198, 172, 170, [['Grilled Chicken', 1]], 'Pool Table 12'],
                ['barbecue', $waiter, $chefBbq, 'completed', 240, 45, 238, 200, 180, [['Grilled Fish', 1]], 'Cabana 04'], // late + slow pickup

                // --- Live now: overdue (red), in-progress (amber), ready, pending ---
                ['grill_kitchen', $waiter, $chefGrill, 'accepted', 50, 35, 48, null, null, [['BBQ Ribs', 1]], 'Sunbed 07'], // overdue
                ['barbecue', $waiter2, $chefBbq, 'accepted', 60, 40, 58, null, null, [['Suya Platter', 2]], 'Pool Table 12'], // overdue
                ['grill_kitchen', $waiter, $chefGrill, 'accepted', 10, 35, 8, null, null, [['Grilled Prawns', 1]], 'Cabana 04'], // amber
                ['ice_cream', $waiter2, $chefIce, 'ready', 20, 8, 18, 10, null, [['Chocolate Sundae', 2]], 'Room 108'], // ready, slow pickup building
                ['main_kitchen', $waiter, $chefMain, 'pending', 2, 25, null, null, null, [['Egusi & Pounded Yam', 1]], 'Sunbed 07'], // new

                // --- Earlier this week/month (so Week/Month totals exceed Today) ---
                ['main_kitchen', $waiter, $chefMain, 'completed', 2 * 1440 + 30, 25, 2 * 1440 + 28, 2 * 1440 + 10, 2 * 1440 + 8, [['Jollof Rice & Chicken', 1]], 'Room 108'],
                ['grill_kitchen', $waiter2, $chefGrill, 'completed', 5 * 1440 + 60, 35, 5 * 1440 + 58, 5 * 1440 + 30, 5 * 1440 + 25, [['Grilled Prawns', 2]], 'Pool Table 12'],
            ];

            foreach ($orders as [$dept, $waitress, $chef, $status, $placedAgo, $target, $acceptedAgo, $readyAgo, $completedAgo, $items, $table]) {
                if (! $waitress || ! $chef) {
                    continue; // accounts missing — HospiDemoSeeder not run
                }

                $placedAt = now()->subMinutes($placedAgo);
                $ago = fn (?int $m): ?Carbon => $m === null ? null : now()->subMinutes($m);

                $total = 0;
                foreach ($items as [$name, $qty]) {
                    $total += (int) ($meals->get($name)?->price ?? 0) * $qty;
                }

                $order = Order::query()->create([
                    'department' => $dept,
                    'placed_by_user_id' => $waitress->id,
                    'chef_user_id' => $chef->id,
                    'table_id' => $tid($table),
                    'status' => $status,
                    'placed_at' => $placedAt,
                    'target_minutes' => $target,
                    'due_at' => $placedAt->copy()->addMinutes($target),
                    'accepted_at' => $ago($acceptedAgo),
                    'ready_at' => $ago($readyAgo),
                    'completed_at' => $ago($completedAgo),
                    'total_amount' => $total,
                ]);

                foreach ($items as [$name, $qty]) {
                    $meal = $meals->get($name);
                    if (! $meal) {
                        continue;
                    }
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'meal_id' => $meal->id,
                        'name_snapshot' => $meal->name,
                        'unit_price' => (int) $meal->price,
                        'quantity' => $qty,
                        'prep_time_minutes_snapshot' => (int) $meal->prep_time_minutes,
                    ]);
                }
            }
        });

        $this->command?->info('Seeded demo orders for Iceland Beach Resort.');
    }

    private function tenantId(): int
    {
        return Tenant::query()->where('slug', 'iceland-beach')->value('id');
    }
}
