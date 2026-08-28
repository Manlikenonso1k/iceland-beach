<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ScanTicket extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationGroup = 'Events';

    protected static ?string $navigationLabel = 'Scan Ticket';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Scan Ticket QR Code';

    protected static ?string $slug = 'scan-ticket';

    protected static string $view = 'filament.pages.scan-ticket';
}
