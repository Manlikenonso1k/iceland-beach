<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestrictedAdminSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_desk_and_scanner_cannot_access_or_see_restricted_sections(): void
    {
        foreach (['Front Desk', 'Scanner'] as $roleName) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            Role::findOrCreate($roleName, 'web');

            $user = User::factory()->create();
            $user->assignRole($roleName);

            foreach ([
                '/admin/users',
                '/admin/roles',
                '/admin/activity-logs',
                '/admin/manage-settings',
                '/admin/referrals',
                '/admin/tickets',
                '/admin/vendor-requests',
                '/admin/scan-ticket',
            ] as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }

            $this->actingAs($user)
                ->get('/admin')
                ->assertOk()
                ->assertDontSee('Administration')
                ->assertDontSee('Events');
        }
    }
}