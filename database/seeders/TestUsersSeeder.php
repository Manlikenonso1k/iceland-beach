<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;

/**
 * Local test accounts for exercising the room / space role split by hand.
 *
 * ⚠️ NOT part of DatabaseSeeder, and it refuses to run in production. These are
 * fixed, well-known passwords — seeding them into a live database would create
 * three guessable staff logins.
 *
 * Run it deliberately, against a local database only:
 *
 *     php artisan db:seed --class=TestUsersSeeder
 *
 * Idempotent: re-running updates the roles and leaves the accounts in place.
 */
class TestUsersSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private const ACCOUNTS = [
        'frontdesk@test.com' => ['name' => 'Front Desk Tester', 'role' => 'Front Desk'],
        'host@test.com' => ['name' => 'Space Host Tester', 'role' => 'Space Host'],
        'admin@test.com' => ['name' => 'Admin Tester', 'role' => 'Super Admin'],
    ];

    public function run(): void
    {
        if (App::environment('production')) {
            $this->command?->error('TestUsersSeeder refuses to run in production — these are well-known passwords.');

            return;
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ACCOUNTS as $email => $meta) {
            Role::findOrCreate($meta['role'], 'web');

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                ['name' => $meta['name'], 'password' => self::PASSWORD],
            );

            // syncRoles, not assignRole: re-running must not accumulate roles.
            $user->syncRoles([$meta['role']]);

            $this->command?->info(sprintf('%s — %s / %s', $meta['role'], $email, self::PASSWORD));
        }
    }
}
