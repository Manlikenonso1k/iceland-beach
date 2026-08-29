<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Creates the local admin account.
 *
 * Without this, a fresh `migrate:fresh --seed` produces no users at all and
 * /admin/login is unreachable — RoleSeeder only assigns roles to accounts that
 * already exist, so it silently no-ops on an empty users table.
 *
 * Runs after RoleSeeder so the roles exist, and assigns Super Admin itself
 * because User::canAccessPanel() gates the panel on role, not credentials.
 *
 * The password comes from ADMIN_PASSWORD and falls back to the documented
 * local default. Set ADMIN_PASSWORD in the environment before seeding anywhere
 * that is not a local machine.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@local.test');
        $password = (string) env('ADMIN_PASSWORD', 'password');

        // 'password' is cast to 'hashed' on the model, so assign it in plain text.
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => $password],
        );

        if (Role::where('name', 'Super Admin')->exists()) {
            $user->assignRole('Super Admin');
        }

        $this->command?->info("Admin ready: {$email}");
    }
}
