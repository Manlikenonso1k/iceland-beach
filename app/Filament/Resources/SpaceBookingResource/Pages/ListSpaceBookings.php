<?php

namespace App\Filament\Resources\SpaceBookingResource\Pages;

use App\Filament\Resources\SpaceBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\Concerns\CanAuthorizeResourceAccess;

class ListSpaceBookings extends ListRecords
{
    // Filament 3's ListRecords::authorizeAccess() is EMPTY, so without this
    // trait the list page is reachable by direct URL regardless of canAccess().
    use CanAuthorizeResourceAccess;

    protected static string $resource = SpaceBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => SpaceBookingResource::canWrite()),
            SpaceBookingResource::downloadReportAction(),
            Actions\Action::make('calendarView')
                ->label('Calendar View')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->url(SpaceBookingResource::getUrl('index')),
        ];
    }
}
