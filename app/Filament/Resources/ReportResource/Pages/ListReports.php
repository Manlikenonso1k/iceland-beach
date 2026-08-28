<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Widgets\PaymentMethodBreakdown;
use App\Filament\Widgets\SalesOverview;
use App\Filament\Resources\ReportResource;
use Filament\Resources\Pages\ListRecords;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesOverview::class,
            PaymentMethodBreakdown::class,
        ];
    }
}
