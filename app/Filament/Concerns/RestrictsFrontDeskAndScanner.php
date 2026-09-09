<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait RestrictsFrontDeskAndScanner
{
    protected static function canAccessRestrictedSection(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && ! $user->hasAnyRole(['Front Desk', 'frontdesk', 'Scanner']);
    }

    public static function canViewAny(): bool { return static::canAccessRestrictedSection(); }
    public static function canCreate(): bool { return static::canAccessRestrictedSection(); }
    public static function canEdit(Model $record): bool { return static::canAccessRestrictedSection(); }
    public static function canDelete(Model $record): bool { return static::canAccessRestrictedSection(); }
    public static function canDeleteAny(): bool { return static::canAccessRestrictedSection(); }
}
