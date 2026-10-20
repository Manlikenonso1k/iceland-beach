<?php

namespace App\Filament\Resources\SpaceBookingResource\Pages;

use App\Filament\Resources\SpaceBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpaceBooking extends EditRecord
{
    protected static string $resource = SpaceBookingResource::class;

    /** EditRecord::authorizeAccess() aborts 403 unless canEdit($record). */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => SpaceBookingResource::canWrite()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return SpaceBookingResource::getUrl('index');
    }
}
