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

    protected function mutateFormDataBeforeSave(array $data): array
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
            excludeId: $this->record->id,
            roomName: $data['room_name'],
            start: Carbon::parse($data['start_date']),
            end: Carbon::parse($data['end_date']),
        );

        return $data;
    }
}
