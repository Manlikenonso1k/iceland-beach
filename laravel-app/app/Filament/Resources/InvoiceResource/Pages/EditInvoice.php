<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewInvoice')
                ->label('View Invoice')
                ->icon('heroicon-o-eye')
                ->url(route('invoices.view', $this->record->id))
                ->openUrlInNewTab(),
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    try {
                        return InvoiceResource::downloadPdf($this->record);
                    } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
                        Notification::make()
                            ->danger()
                            ->title('PDF Service Unavailable')
                            ->body('DOMPDF package not installed. On production, run: composer require barryvdh/laravel-dompdf:^2.1')
                            ->send();
                        return null;
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error Generating PDF')
                            ->body($e->getMessage())
                            ->send();
                        return null;
                    }
                }),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->can('delete', $this->record) ?? false),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->syncCalculatedTotals();
    }
}
