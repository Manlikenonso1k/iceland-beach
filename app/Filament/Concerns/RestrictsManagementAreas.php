<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps FLOOR roles out of the management areas: Administration (users, roles,
 * activity log, settings) and Events (tickets, scan logs, referrals, vendor
 * requests).
 *
 * ⚠️ Renamed from RestrictsFrontDeskAndScanner, and the rename is the point.
 * A trait named after two specific roles invited exactly one mistake: 'Space
 * Host' was added to User::canAccessPanel() and nobody thought to look inside a
 * trait named for Front Desk and Scanner. The result was a Space Host with
 * /admin/users AND /admin/roles — a privilege-escalation path, since the role
 * editor can grant Super Admin. Name the AREA, not the roles, so the next role
 * added has an obvious place to be considered.
 *
 * Add any new non-management role to FLOOR_ROLES below.
 */
trait RestrictsManagementAreas
{
    /**
     * Roles confined to day-to-day floor work. They keep their own operational
     * areas (bookings, space bookings, scanning where applicable) and are
     * barred from Administration and Events.
     *
     * 'Host' is listed alongside 'Space Host' because both names are accepted
     * as the space-booking role — see User::PANEL_ROLES.
     */
    public const FLOOR_ROLES = [
        'Front Desk',
        'frontdesk',
        'Scanner',
        'Space Host',
        'Host',
        'Waiter',
    ];

    protected static function canAccessRestrictedSection(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && ! $user->hasAnyRole(self::FLOOR_ROLES);
    }

    /**
     * Gates navigation AND the route. Filament 3's
     * ListRecords::authorizeAccess() is empty, so canViewAny alone does not
     * stop a direct URL — canAccess is what the resource pages abort on.
     */
    public static function canAccess(): bool
    {
        return auth()->guard('web')->check() && static::canAccessRestrictedSection();
    }

    public static function canViewAny(): bool
    {
        return static::canAccessRestrictedSection();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccessRestrictedSection();
    }

    public static function canCreate(): bool
    {
        return static::canAccessRestrictedSection();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccessRestrictedSection();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccessRestrictedSection();
    }

    public static function canDeleteAny(): bool
    {
        return static::canAccessRestrictedSection();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canAccessRestrictedSection();
    }

    public static function canRestore(Model $record): bool
    {
        return static::canAccessRestrictedSection();
    }
}
