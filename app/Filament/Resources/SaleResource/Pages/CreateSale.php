<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\PosService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    /**
     * Replace Filament's plain Sale::create() with the full transactional POS flow:
     * table guard, duplicate window, stock check, void_reason enforcement,
     * sale + items insert, stock decrement, void_logs, table status.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(PosService::class)->processSale(
            waiterId: (int) ($data['waiter_id'] ?? 0),
            tableId: (int) ($data['table_id'] ?? 0),
            paymentMethod: $data['payment_method'] ?? 'cash',
            voidReason: $data['void_reason'] ?? '',
            items: $data['items'] ?? [],
        );
    }
}
