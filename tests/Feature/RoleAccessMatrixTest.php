<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The whole access matrix, pinned.
 *
 * This exists because of a real escalation bug: 'Space Host' was added to
 * User::canAccessPanel() but not to the area-restriction trait, so it could
 * open /admin/users AND /admin/roles — meaning it could grant itself Super
 * Admin. Per-feature tests all passed; nothing asserted the matrix as a whole.
 *
 * So this test is deliberately exhaustive and deliberately strict: every role
 * is checked against every area, and an area NOT listed as allowed must return
 * 403. Adding a new role or resource should break this test until the matrix is
 * updated on purpose.
 */
class RoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** Every admin area under test. */
    private const AREAS = [
        'users' => '/admin/users',
        'roles' => '/admin/roles',
        'activity-logs' => '/admin/activity-logs',
        'manage-settings' => '/admin/manage-settings',
        'tickets' => '/admin/tickets',
        'ticket-scan-logs' => '/admin/ticket-scan-logs',
        'referrals' => '/admin/referrals',
        'vendor-requests' => '/admin/vendor-requests',
        'scan-ticket' => '/admin/scan-ticket',
        'bookings' => '/admin/bookings',
        'space-bookings' => '/admin/space-bookings',
        'rooms' => '/admin/rooms',
        'spaces' => '/admin/spaces',
        'invoices' => '/admin/invoices',
        'sales' => '/admin/sales',
        'reports' => '/admin/reports',
        'inventory-items' => '/admin/inventory-items',
    ];

    /**
     * Exactly what each role may OPEN. Anything absent must be refused.
     *
     * Space Host and Host are identical by design — 'Host' is an accepted alias.
     * Neither gets Administration, Events or anything ticket-related.
     */
    private const ALLOWED = [
        'Space Host' => ['bookings', 'space-bookings', 'invoices', 'sales', 'reports', 'inventory-items'],
        'Host' => ['bookings', 'space-bookings', 'invoices', 'sales', 'reports', 'inventory-items'],
        'Front Desk' => ['bookings', 'space-bookings', 'invoices', 'sales', 'reports', 'inventory-items'],
        // Scanner keeps the scan page — gate entry is the role's entire job.
        'Scanner' => ['scan-ticket', 'bookings', 'space-bookings', 'invoices', 'sales', 'reports', 'inventory-items'],
        // Waiter is not in User::PANEL_ROLES at all, so it reaches nothing.
        'Waiter' => [],
    ];

    private function userWithRole(string $roleName): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($roleName, 'web');

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    public function test_every_floor_role_sees_exactly_its_allowed_areas(): void
    {
        foreach (self::ALLOWED as $roleName => $allowed) {
            $user = $this->userWithRole($roleName);

            foreach (self::AREAS as $key => $url) {
                $status = $this->actingAs($user)->get($url)->getStatusCode();

                if (in_array($key, $allowed, true)) {
                    $this->assertSame(200, $status, "{$roleName} should reach {$url}");

                    continue;
                }

                $this->assertNotSame(
                    200,
                    $status,
                    "SECURITY: {$roleName} must NOT reach {$url} (got {$status})",
                );
            }
        }
    }

    /**
     * The specific escalation that occurred. Kept as its own named test so a
     * failure says what it means.
     */
    public function test_no_floor_role_can_reach_user_or_role_management(): void
    {
        foreach (['Space Host', 'Host', 'Front Desk', 'Scanner', 'Waiter'] as $roleName) {
            $user = $this->userWithRole($roleName);

            foreach (['/admin/users', '/admin/roles'] as $url) {
                $this->assertNotSame(
                    200,
                    $this->actingAs($user)->get($url)->getStatusCode(),
                    "PRIVILEGE ESCALATION: {$roleName} can reach {$url} and could grant itself Super Admin",
                );
            }
        }
    }

    public function test_no_floor_role_reaches_anything_ticket_related(): void
    {
        // Scanner is the one exception and only for the scan page itself: it may
        // admit guests, but not read ticket records or scan history.
        foreach (['Space Host', 'Host', 'Front Desk', 'Waiter'] as $roleName) {
            $user = $this->userWithRole($roleName);

            foreach (['/admin/tickets', '/admin/ticket-scan-logs', '/admin/scan-ticket'] as $url) {
                $this->assertNotSame(
                    200,
                    $this->actingAs($user)->get($url)->getStatusCode(),
                    "{$roleName} must not reach ticket area {$url}",
                );
            }
        }

        $scanner = $this->userWithRole('Scanner');
        $this->actingAs($scanner)->get('/admin/scan-ticket')->assertOk();
        $this->actingAs($scanner)->get('/admin/tickets')->assertForbidden();
        $this->actingAs($scanner)->get('/admin/ticket-scan-logs')->assertForbidden();
    }

    /**
     * 'Host' and 'Space Host' must behave IDENTICALLY everywhere.
     *
     * This test exists because the alias leaked twice. It was propagated to the
     * gates that GRANT access (User::PANEL_ROLES,
     * RestrictsSpaceBookingWrites::SPACE_WRITERS) but not to the one that
     * WITHHOLDS it (RestrictsRoomBookingWrites::ROOM_READ_ONLY_ROLES), so a
     * user with the role named plainly 'Host' got room-booking WRITE while
     * 'Space Host' got 403.
     *
     * Checking view access alone would not have caught it — both roles could
     * VIEW rooms. The divergence was only on writing.
     */
    public function test_the_host_alias_behaves_identically_to_space_host(): void
    {
        $writeUrls = [
            '/admin/bookings/create',
            '/admin/space-bookings/create',
        ];

        $statuses = [];

        foreach (['Space Host', 'Host'] as $roleName) {
            $user = $this->userWithRole($roleName);

            foreach (array_merge(array_values(self::AREAS), $writeUrls) as $url) {
                $statuses[$roleName][$url] = $this->actingAs($user)->get($url)->getStatusCode();
            }
        }

        $this->assertSame(
            $statuses['Space Host'],
            $statuses['Host'],
            "'Host' and 'Space Host' are aliases and must resolve identically on every URL, "
            . 'including write URLs. A difference means an alias was added to some gates but not all.',
        );

        // And specifically: neither may write a ROOM booking.
        $this->assertSame(403, $statuses['Space Host']['/admin/bookings/create']);
        $this->assertSame(403, $statuses['Host']['/admin/bookings/create']);

        // But both must write a SPACE booking — that is the role's job.
        $this->assertSame(200, $statuses['Space Host']['/admin/space-bookings/create']);
        $this->assertSame(200, $statuses['Host']['/admin/space-bookings/create']);
    }

    /**
     * Write access, not just viewing. The escalation bug and the alias bug were
     * both invisible to a view-only matrix.
     */
    public function test_floor_roles_cannot_write_where_they_may_only_look(): void
    {
        foreach (['Space Host', 'Host', 'Front Desk', 'Scanner'] as $roleName) {
            $user = $this->userWithRole($roleName);

            foreach ([
                '/admin/users/create',
                '/admin/roles/create',
                '/admin/tickets/create',
            ] as $url) {
                $this->assertNotSame(
                    200,
                    $this->actingAs($user)->get($url)->getStatusCode(),
                    "SECURITY: {$roleName} must not reach {$url}",
                );
            }
        }
    }

    public function test_super_admin_still_reaches_everything(): void
    {
        // The restriction must not have cost the admin anything.
        $admin = $this->userWithRole('Super Admin');

        foreach (self::AREAS as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_administration_remains_super_admin_only(): void
    {
        // Manager is NOT a floor role, so it keeps Events and user management —
        // but the activity log and settings stay Super Admin only.
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)->get('/admin/activity-logs')->assertForbidden();
        $this->actingAs($manager)->get('/admin/manage-settings')->assertForbidden();
    }
}
