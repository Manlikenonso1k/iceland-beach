<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Support\NairaAmountFormatter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Resort Operations';

    protected static ?string $navigationLabel = 'Invoices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Invoice Details')
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->default(fn (): string => Invoice::generateInvoiceNumber())
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(40),
                        DatePicker::make('invoice_date')
                            ->label('Date')
                            ->default(now())
                            ->required(),
                        TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_address')
                            ->label('Address')
                            ->maxLength(255),
                        TextInput::make('telephone')
                            ->label('Telephone')
                            ->tel()
                            ->maxLength(40),
                    ])
                    ->columns(2),

                Section::make('Invoice Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->live()
                            ->reorderable(false)
                            ->schema([
                                TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->datalist([
                                        'Adult Gate Fee',
                                        'Children Gate Fee',
                                        'Adult Corkage',
                                        'Children Corkage',
                                        'Stretcher Cabana',
                                        'Double Bed Cabana',
                                        'Adult Swimming Fee',
                                        'Children Swimming Fee',
                                    ])
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->label('QTY')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        static::updateItemSubTotal($get, $set);
                                    }),
                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->prefix('N')
                                    ->minValue(0)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        static::updateItemSubTotal($get, $set);
                                    }),
                                TextInput::make('sub_total')
                                    ->label('Sub-total')
                                    ->numeric()
                                    ->prefix('N')
                                    ->readOnly()
                                    ->required()
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->afterStateHydrated(function (Get $get, Set $set, ?array $state): void {
                                static::syncInvoiceTotals($set, $state ?? []);
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, ?array $state): void {
                                static::syncInvoiceTotals($set, $state ?? []);
                            })
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::normalizeItemData($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::normalizeItemData($data)),

                        TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->prefix('N')
                            ->readOnly()
                            ->required()
                            ->default(0),
                        TextInput::make('total_in_words')
                            ->label('Total in Words')
                            ->readOnly()
                            ->maxLength(255)
                            ->required(),
                    ]),

                Section::make('Bank Details')
                    ->description('Prepopulated default account for invoice payments.')
                    ->schema([
                        TextInput::make('bank_name')
                            ->label('Bank')
                            ->default((string) config('resort.invoice.bank_name', 'MONIEPOINT'))
                            ->required(),
                        TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->default((string) config('resort.invoice.bank_account_number', '5029208012'))
                            ->required(),
                        TextInput::make('bank_account_name')
                            ->label('Account Name')
                            ->default((string) config('resort.invoice.bank_account_name', 'NEW ICELAND BEACH RESORT'))
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice #')->searchable()->sortable(),
                TextColumn::make('invoice_date')->label('Date')->date('Y-m-d')->sortable(),
                TextColumn::make('customer_name')->label('Customer')->searchable(),
                TextColumn::make('telephone')->label('Telephone')->toggleable(),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->formatStateUsing(fn ($state) => '₦' . number_format($state, 2))
                    ->sortable(),
                TextColumn::make('total_in_words')->label('Total in Words')->limit(50)->toggleable(),
            ])
            ->actions([
                Action::make('viewInvoice')
                    ->label('View Invoice')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Invoice $record) => route('invoices.view', $record->id))
                    ->openUrlInNewTab(),
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Invoice $record) {
                        try {
                            return static::downloadPdf($record);
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([])
            ->paginated(false);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function downloadPdf(Invoice $invoice): StreamedResponse
    {
        $invoice->loadMissing('items');

        $pdf = app('dompdf.wrapper');

        if (! is_object($pdf)) {
            throw new \RuntimeException('PDF service is not available.');
        }

        call_user_func([$pdf, 'loadView'], 'pdf.invoice', [
            'invoice' => $invoice,
        ]);

        $filename = 'invoice-' . ltrim((string) $invoice->invoice_number, '#') . '.pdf';

        return response()->streamDownload(static function () use ($pdf): void {
            echo (string) call_user_func([$pdf, 'output']);
        }, $filename);
    }

    protected static function updateItemSubTotal(Get $get, Set $set): void
    {
        $quantity = max(1, (int) ($get('quantity') ?? 1));
        $unitPrice = (float) ($get('unit_price') ?? 0);

        $set('sub_total', round($quantity * $unitPrice, 2));
    }

    protected static function normalizeItemData(array $data): array
    {
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $subTotal = round($quantity * $unitPrice, 2);

        return [
            ...$data,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'sub_total' => $subTotal,
        ];
    }

    protected static function syncInvoiceTotals(Set $set, array $items): void
    {
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $total += round($quantity * $unitPrice, 2);
        }

        $set('total_amount', round($total, 2));
        $set('total_in_words', NairaAmountFormatter::toWords($total));
    }
}
