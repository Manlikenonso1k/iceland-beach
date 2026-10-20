<?php

namespace App\Filament\Resources\SpaceBookingResource\Pages;

use App\Filament\Resources\SpaceBookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpaceBooking extends CreateRecord
{
    protected static string $resource = SpaceBookingResource::class;

    /**
     * CreateRecord::authorizeAccess() already aborts 403 unless canCreate(),
     * which the RestrictsSpaceBookingWrites trait answers — so a read-only
     * Front Desk user hitting /admin/space-bookings/create by URL is refused
     * without any extra code here.
     */
    protected function getRedirectUrl(): string
    {
        return SpaceBookingResource::getUrl('index');
    }
}
