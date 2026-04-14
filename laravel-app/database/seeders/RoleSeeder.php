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
            'Waiter',
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
