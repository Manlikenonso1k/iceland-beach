<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class ScanTicket extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationGroup = 'Events';

    protected static ?string $navigationLabel = 'Scan Ticket';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Scan Ticket QR Code';

    protected static ?string $slug = 'scan-ticket';

    protected static string $view = 'filament.pages.scan-ticket';

    /**
     * Scanner is deliberately NOT excluded here.
     *
     * This page lives in the Events navigation group, so when Events was
     * restricted for Front Desk and Scanner it swept up the one page the
     * Scanner role exists for — a scanner who cannot scan. Gate entry is that
     * role's entire job.
     *
     * Front Desk stays out: rooms and bookings are their remit, and they have no
     * reason to admit guests at the gate.
     *
     * Note this is a denylist, not an allowlist, so the owner account (which
     * User::canAccessPanel() admits by email address, possibly with no role
     * assigned at all) is not locked out.
     */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        // Scanner KEEPS this — gate entry is the role's entire job. Front
        // Desk, Space Host/Host and Waiter have no reason to admit guests.
        return $user instanceof User
            && ! $user->hasAnyRole(['Front Desk', 'frontdesk', 'Space Host', 'Host', 'Waiter']);
    }
}
