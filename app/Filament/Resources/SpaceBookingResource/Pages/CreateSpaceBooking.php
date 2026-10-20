<?php

namespace App\Filament\Resources\SpaceBookingResource\Pages;

use App\Filament\Resources\SpaceBookingResource;
use App\Models\SpaceBooking;
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

    /**
     * Anything created here was taken in the backend by staff, so money changed
     * hands off-system and a receipt is required before it can be confirmed.
     * The column defaults to 'website' for rows from the public endpoint, so
     * this is the one place that marks a booking manual.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = SpaceBooking::SOURCE_MANUAL;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return SpaceBookingResource::getUrl('index');
    }
}
