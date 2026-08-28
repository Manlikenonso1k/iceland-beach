<?php

namespace App\Filament\Resources\TicketScanLogResource\Pages;

use App\Filament\Resources\TicketScanLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicketScanLog extends EditRecord
{
    protected static string $resource = TicketScanLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
