<?php

namespace Database\Seeders;

use App\Models\Meal;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Models\VenueTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A ready-to-demo Hospi Sales tenant: one business, one account per floor role
 * with known credentials, and its prices. Lets the app be logged into and
 * clicked through immediately. Idempotent — safe to re-run.
 *
 *   php artisan db:seed --class=Database\\Seeders\\HospiDemoSeeder
 */
class HospiDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            HospiRoleSeeder::class,
        ]);

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'iceland-beach'],
            [
                'name' => 'Iceland Beach Resort',
                'primary_colour' => '#002F61',
                'enabled_departments' => [
                    'gate_sales', 'hosts', 'front_desk',
                    'ice_cream', 'main_kitchen', 'grill_kitchen', 'barbecue',
                ],
                'timezone' => 'Africa/Lagos',
                'is_active' => true,
            ],
        );

        Tenant::runAs($tenant->id, function (): void {
            TenantSetting::set('gate_ticket', 300000, 'integer');       // ₦3,000
            TenantSetting::set('per_head', 200000, 'integer');          // ₦2,000
            TenantSetting::set('swimming_per_head', 200000, 'integer'); // ₦2,000

            // name, category, price(kobo), prep minutes, department
            $meals = [
                ['Jollof Rice & Chicken', 'Rice', 450000, 25, 'main_kitchen'],
                ['Fried Rice & Turkey', 'Rice', 500000, 30, 'main_kitchen'],
                ['Egusi & Pounded Yam', 'Swallow', 550000, 35, 'main_kitchen'],
                ['Grilled Chicken', 'Grill', 650000, 35, 'grill_kitchen'],
                ['BBQ Ribs', 'Grill', 800000, 50, 'grill_kitchen'],
                ['Grilled Prawns', 'Grill', 900000, 30, 'grill_kitchen'],
                ['Suya Platter', 'Grill', 600000, 40, 'barbecue'],
                ['Grilled Fish', 'Grill', 700000, 45, 'barbecue'],
                ['Vanilla Scoop', 'Ice Cream', 150000, 5, 'ice_cream'],
                ['Chocolate Sundae', 'Ice Cream', 250000, 8, 'ice_cream'],
            ];

            foreach ($meals as [$name, $category, $price, $prep, $department]) {
                Meal::query()->updateOrCreate(
                    ['department' => $department, 'name' => $name],
                    ['category' => $category, 'price' => $price, 'prep_time_minutes' => $prep, 'is_available' => true],
                );
            }

            foreach ([['Cabana 04', 'Poolside Deck'], ['Pool Table 12', 'Pool'], ['Sunbed 07', 'Beachfront'], ['Room 108', 'Hotel']] as [$tname, $zone]) {
                VenueTable::query()->updateOrCreate(['name' => $tname], ['zone' => $zone, 'is_active' => true]);
            }
        });

        // email, phone, role, department
        $accounts = [
            ['Demo Manager',  'manager@hospi.test', '08100000001', 'Manager', null],
            ['Demo Waitress',   'waiter@hospi.test',  '08100000002', 'Waiter', null],
            ['Demo Waitress 2', 'waiter2@hospi.test', '08100000009', 'Waiter', null],
            ['Demo Chef',       'chef@hospi.test',      '08100000003', 'Chef', 'main_kitchen'],
            ['Demo CEO',        'ceo@hospi.test',       '08100000004', 'CEO',  null],
            ['Demo Host',       'host@hospi.test',      '08100000005', 'Host', null],
            ['Demo Grill Chef', 'grillchef@hospi.test', '08100000006', 'Chef', 'grill_kitchen'],
            ['Demo BBQ Chef',       'bbqchef@hospi.test',      '08100000007', 'Chef', 'barbecue'],
            ['Demo Ice Cream Chef', 'icecreamchef@hospi.test', '08100000008', 'Chef', 'ice_cream'],
        ];

        foreach ($accounts as [$name, $email, $phone, $role, $department]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'department' => $department,
                    'tenant_id' => $tenant->id,
                    'password' => Hash::make('password'),
                ],
            );

            $user->syncRoles([$role]);
        }

        // Fill the board / kitchen / revenue with a realistic spread of orders.
        $this->call(HospiDemoOrdersSeeder::class);
    }
}
