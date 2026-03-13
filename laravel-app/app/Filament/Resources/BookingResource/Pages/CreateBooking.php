<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Services\ReservationService;
use Illuminate\Support\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        if (
            $record->is_booked !== 'booked'
            || empty($record->customer_name)
            || empty($record->email)
            || empty($record->start_date)
            || empty($record->end_date)
        ) {
            return;
        }

        app(ReservationService::class)->reserveRoom(
            room: $record,
            customerName: $record->customer_name,
            customerEmail: $record->email,
            phone: null,
            guests: null,
            start: Carbon::parse($record->start_date),
            end: Carbon::parse($record->end_date),
            sendEmails: true,
        );
    }
}
