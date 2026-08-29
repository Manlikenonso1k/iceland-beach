<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use App\Filament\Resources\ReferralResource;
use App\Models\Referral;
use Filament\Resources\Pages\CreateRecord;

class CreateReferral extends CreateRecord
{
    protected static string $resource = ReferralResource::class;

    /** Guarantees a code even if the field was cleared before saving. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['code'] ?? null)) {
            $data['code'] = Referral::generateCode();
        }

        $data['code'] = strtoupper(trim($data['code']));

        return $data;
    }
}
