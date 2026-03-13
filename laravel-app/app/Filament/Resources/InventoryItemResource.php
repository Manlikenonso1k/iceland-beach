<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryItemResource\Pages;
use App\Models\InventoryItem;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Resort Operations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('category')->maxLength(255),
                TextInput::make('price')->required()->numeric()->prefix('NGN'),
                TextInput::make('stock_quantity')->required()->numeric()->minValue(0),
                TextInput::make('low_stock_threshold')->required()->numeric()->minValue(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')->searchable()->toggleable(),
                TextColumn::make('price')->money('NGN', divideBy: 1)->sortable(),
                TextColumn::make('stock_quantity')->sortable(),
                TextColumn::make('low_stock_threshold')->label('Low Stock At'),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->query(fn ($query) => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
                ->bulkActions([]);
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
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }
}
