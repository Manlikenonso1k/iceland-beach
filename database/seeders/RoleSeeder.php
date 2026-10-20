<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Super Admin',
            'Admin',
            'Manager',
            'Front Desk',
            // Manages SPACE (venue) bookings, with the same powers Front Desk
            // has over rooms. Named 'Space Host', not 'Host', because the
            // planned Hospi Sales mobile app defines a separate restaurant
            // Host role (tables/beds/cabins) — see HOSPI_SALES_BRIEF.md. Two
            // unrelated jobs must not share one role name.
            'Space Host',
            'Waiter',
            'Scanner',
            // Referral partners: read-only access to their own code and sales.
            'Referral',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        // Ensure known admin accounts can manage approvals.
        $superAdminEmails = [
            'victorynonso9@gmail.com',
            'admin@local.test',
        ];

        foreach ($superAdminEmails as $email) {
            $user = User::query()->where('email', $email)->first();
            if ($user) {
                $user->assignRole('Super Admin');
            }
        }
    }
}
