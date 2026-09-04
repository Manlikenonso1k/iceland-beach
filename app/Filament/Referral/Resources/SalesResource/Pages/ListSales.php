<?php

namespace App\Filament\Referral\Resources\SalesResource\Pages;

use App\Filament\Referral\Resources\SalesResource;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SalesResource::class;

    /** Read-only: no create button. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
