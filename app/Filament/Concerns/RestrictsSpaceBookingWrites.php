<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may CHANGE a space booking.
 *
 * Space Host owns this area, with the same powers Front Desk has over rooms.
 * Front Desk gets read-only cross-visibility: it can see space bookings and
 * space reports but cannot create, edit, confirm, deny or delete them.
 *
 * An allowlist rather than a denylist, which is the opposite of
 * RestrictsFrontDeskAndScanner. That trait had to preserve the behaviour of an
 * area that already existed; this resource is brand new, so nothing is being
 * taken away from anyone and the safer default is to name who may write.
 *
 * ⚠️ Viewing is NOT gated here — canViewAny stays open to all staff, which is
 * what makes the cross-view read-only rather than invisible. And because
 * Filament 3's ListRecords::authorizeAccess() is empty, the confirm/reject
 * table actions must re-check `canWrite()` themselves; overriding canEdit alone
 * does not cover a custom action.
 */
trait RestrictsSpaceBookingWrites
{
    /** Roles permitted to change space bookings. */
    public const SPACE_WRITERS = [
        'Super Admin',
        'super_admin',
        'Admin',
        'admin',
        'Manager',
        'Space Host',
    ];

    public static function canWrite(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(self::SPACE_WRITERS);
    }

    public static function canCreate(): bool
    {
        return static::canWrite();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWrite();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canWrite();
    }

    public static function canDeleteAny(): bool
    {
        return static::canWrite();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canWrite();
    }

    public static function canRestore(Model $record): bool
    {
        return static::canWrite();
    }
}
