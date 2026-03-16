<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Mail\SaleReceiptMail;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Waiter;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Resort Operations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('waiter_id')
                    ->label('Waiter')
                    ->options(Waiter::query()->pluck('full_name', 'id'))
                    ->required(),
                Select::make('table_id')
                    ->label('Table')
                    ->options(DiningTable::query()->pluck('table_name', 'id'))
                    ->required(),
                TextInput::make('total_amount')->numeric()->prefix('NGN')->hiddenOn('create'),
                Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'card' => 'Card',
                    ])
                    ->required(),
                DatePicker::make('sale_date')->hiddenOn('create'),
                TextInput::make('void_reason')
                    ->label('Void reason (required if any item is voided)')
                    ->maxLength(500)
                    ->visibleOn('create'),
                Repeater::make('items')
                    ->label('Order items')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(Product::query()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Toggle::make('is_voided')
                            ->label('Void')
                            ->default(false),
                    ])
                    ->columns(3)
                    ->minItems(1)
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('waiter.full_name')->label('Waiter')->searchable(),
                TextColumn::make('diningTable.table_name')->label('Table')->searchable(),
                TextColumn::make('payment_method')->badge(),
                TextColumn::make('total_amount')->money('NGN', divideBy: 1)->sortable(),
                TextColumn::make('sale_date')->date('Y-m-d')->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'card' => 'Card',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('sendReceipt')
                    ->label('Send Receipt')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        TextInput::make('email')->email()->required(),
                    ])
                    ->action(function (Sale $record, array $data): void {
                        Mail::to($data['email'])->send(new SaleReceiptMail($record));

                        Notification::make()
                            ->title('Receipt sent successfully')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
                ->bulkActions([]);
    }

        public static function infolist(Infolist $infolist): Infolist
        {
            return $infolist->schema([
                TextEntry::make('id')->label('Sale #'),
                TextEntry::make('waiter.full_name')->label('Waiter'),
                TextEntry::make('diningTable.table_name')->label('Table'),
                TextEntry::make('payment_method')->badge(),
                TextEntry::make('total_amount')->money('NGN', divideBy: 1),
                TextEntry::make('sale_date')->date('Y-m-d'),
            ]);
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
