<?php

namespace App\Filament\Resources\SpaceBookingResource\Pages;

use App\Filament\Resources\SpaceBookingResource;
use App\Filament\Widgets\PendingSpaceBookingsWidget;
use App\Filament\Widgets\SpaceBookingCalendarWidget;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

/**
 * Index page for space bookings: the calendar, mirroring BookingCalendar.
 *
 * ⚠️ Do NOT add <x-filament-widgets::widgets> to the Blade view.
 * <x-filament-panels::page> already renders getHeaderWidgets(), and calling it
 * again renders every widget twice — that was the duplicate-widget bug on the
 * room calendar.
 */
class SpaceBookingCalendar extends Page
{
    protected static string $resource = SpaceBookingResource::class;

    protected static string $view = 'filament.resources.space-booking-resource.pages.space-booking-calendar';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newSpaceBooking')
                ->label('New Space Booking')
                ->icon('heroicon-o-plus')
                // Read-only roles (Front Desk) see the calendar but get no
                // create button; the server gate is canCreate() on the resource.
                ->visible(fn (): bool => SpaceBookingResource::canWrite())
                ->url(SpaceBookingResource::getUrl('create')),
            SpaceBookingResource::downloadReportAction(),
            Action::make('tableView')
                ->label('Table View')
                ->icon('heroicon-o-table-cells')
                ->url(SpaceBookingResource::getUrl('list')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SpaceBookingCalendarWidget::class,
            PendingSpaceBookingsWidget::class,
        ];
    }
}
