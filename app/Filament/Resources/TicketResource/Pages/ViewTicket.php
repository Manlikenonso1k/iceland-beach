<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketPaymentRefreshService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshPayment')
                ->label('Refresh payment')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn (Ticket $record): bool => $record->payment_gateway === 'tgipay')
                ->requiresConfirmation()
                ->modalHeading('Refresh TGI Pay payment status')
                ->modalDescription('Check the gateway using the transaction reference, then generate and email the QR receipt if the payment is successful.')
                ->action(function (Ticket $record): void {
                    $result = app(TicketPaymentRefreshService::class)->refresh($record);

                    $notification = Notification::make()->title($result['message']);

                    if ($result['ok']) {
                        $notification->success();
                    } else {
                        $notification->danger();
                    }

                    $notification->send();
                }),
        ];
    }
}
