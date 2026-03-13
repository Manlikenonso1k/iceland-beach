<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Services\ReservationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
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
            sendEmails: false,
        );
    }
}
