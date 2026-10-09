<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Adds the Hospi Sales floor roles the resort's own RoleSeeder does not define.
 * Manager and Waiter already exist there and are reused as-is; this only fills
 * the gaps (Chef, CEO, Host). Additive and idempotent — never edit RoleSeeder.
 */
class HospiRoleSeeder extends Seeder
{
    /** Roles Hospi Sales introduces on top of the seeded resort roles. */
    public const ROLES = ['Chef', 'CEO', 'Host'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
