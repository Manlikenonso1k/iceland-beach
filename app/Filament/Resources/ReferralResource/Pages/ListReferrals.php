<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use App\Filament\Resources\ReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferrals extends ListRecords
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        // CreateAction hides itself when ReferralResource::canCreate() is false,
        // so referral partners get a read-only list of their own record.
        return [
            Actions\CreateAction::make(),
        ];
    }
}
