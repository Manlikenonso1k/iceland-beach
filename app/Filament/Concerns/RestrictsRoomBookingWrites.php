<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * The mirror image of RestrictsSpaceBookingWrites: Space Host may VIEW room
 * bookings and room reports but must never change them.
 *
 * Deliberately a DENYLIST of exactly one role, not an allowlist. Room booking
 * already works and host.md's hard requirement is "don't change how room
 * booking or Front Desk works today" — an allowlist would silently revoke write
 * access from anyone whose role it failed to enumerate (Scanner and Waiter can
 * currently reach /admin/bookings, and the owner account is admitted by email).
 * Naming only the single role being newly restricted cannot regress anyone else.
 *
 * ⚠️ As with the space trait, the confirm/reject table actions must re-check
 * `canWriteRoomBooking()` themselves — overriding canEdit does not cover a
 * custom action.
 */
trait RestrictsRoomBookingWrites
{
    /** Roles that may see room bookings but not touch them. */
    public const ROOM_READ_ONLY_ROLES = ['Space Host'];

    public static function canWriteRoomBooking(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return ! $user->hasAnyRole(self::ROOM_READ_ONLY_ROLES);
    }

    public static function canCreate(): bool
    {
        return static::canWriteRoomBooking();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWriteRoomBooking();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canWriteRoomBooking();
    }

    public static function canDeleteAny(): bool
    {
        return static::canWriteRoomBooking();
    }
}
