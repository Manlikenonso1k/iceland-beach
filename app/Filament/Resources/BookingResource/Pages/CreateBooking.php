<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Services\ReservationService;
use Illuminate\Support\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (
            ($data['is_booked'] ?? null) !== 'booked'
            || empty($data['room_name'])
            || empty($data['start_date'])
            || empty($data['end_date'])
        ) {
            return $data;
        }

        app(ReservationService::class)->assertNotOverlapping(
            excludeId: null,
            roomName: $data['room_name'],
            start: Carbon::parse($data['start_date']),
            end: Carbon::parse($data['end_date']),
        );

        return $data;
    }

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

        app(ReservationService::class)->sendBookingEmails($record);
    }
}
