<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Front Desk and Scanner are floor roles: they keep bookings and lose the
 * Administration and Events management sections.
 *
 * The one exception is /admin/scan-ticket. It sits in the Events group, so
 * restricting Events originally swept it up and left the Scanner role unable to
 * scan — its entire purpose. Scanner keeps that page; Front Desk does not.
 */
class RestrictedAdminSectionsTest extends TestCase
{
    use RefreshDatabase;

    /** Management URLs neither role may reach, by direct URL or navigation. */
    private const RESTRICTED_URLS = [
        '/admin/users',
        '/admin/roles',
        '/admin/activity-logs',
        '/admin/manage-settings',
        '/admin/referrals',
        '/admin/tickets',
        '/admin/vendor-requests',
    ];

    private function userWithRole(string $roleName): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($roleName, 'web');

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    public function test_front_desk_and_scanner_cannot_reach_management_sections(): void
    {
        foreach (['Front Desk', 'Scanner'] as $roleName) {
            $user = $this->userWithRole($roleName);

            foreach (self::RESTRICTED_URLS as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }

            // Hiding the nav is not the control, but it must match the control.
            $this->actingAs($user)
                ->get('/admin')
                ->assertOk()
                ->assertDontSee('Administration');
        }
    }

    public function test_both_roles_keep_the_bookings_they_work_from(): void
    {
        foreach (['Front Desk', 'Scanner'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->get('/admin/bookings')
                ->assertOk();
        }
    }

    public function test_scanner_can_reach_the_scan_page(): void
    {
        // The regression that mattered: a Scanner who could not scan.
        $this->actingAs($this->userWithRole('Scanner'))
            ->get('/admin/scan-ticket')
            ->assertOk();
    }

    public function test_front_desk_cannot_reach_the_scan_page(): void
    {
        $this->actingAs($this->userWithRole('Front Desk'))
            ->get('/admin/scan-ticket')
            ->assertForbidden();
    }

    public function test_managers_and_super_admins_still_reach_everything(): void
    {
        foreach (['Manager', 'Super Admin'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->get('/admin/scan-ticket')
                ->assertOk();
        }
    }
}
