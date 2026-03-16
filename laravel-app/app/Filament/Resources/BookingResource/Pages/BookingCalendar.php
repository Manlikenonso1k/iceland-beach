<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Widgets\BookingCalendarWidget;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class BookingCalendar extends Page
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.resources.booking-resource.pages.booking-calendar';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newBooking')
                ->label('New Booking')
                ->icon('heroicon-o-plus')
                ->url(BookingResource::getUrl('create')),
            Action::make('tableView')
                ->label('Table View')
                ->icon('heroicon-o-table-cells')
                ->url(BookingResource::getUrl('list')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BookingCalendarWidget::class,
        ];
    }
}
