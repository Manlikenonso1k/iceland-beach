<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketScanLogResource\Pages;
use App\Filament\Resources\TicketScanLogResource\RelationManagers;
use App\Models\TicketScanLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketScanLogResource extends Resource
{
    // Ticket scan history is Events data. It had only a web-guard check, so
    // every floor role could open /admin/ticket-scan-logs.
    use \App\Filament\Concerns\RestrictsManagementAreas;


    protected static ?string $model = TicketScanLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ticket_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('user_id')
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket.name')
                    ->label('Ticket Holder')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticket.order_ref')
                    ->label('Order Ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Scanned By')
                    ->default('System / Public')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ok' => 'success',
                        'already' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Scanned At')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // No edit action for logs
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListTicketScanLogs::route('/'),
            'create' => Pages\CreateTicketScanLog::route('/create'),
            'edit' => Pages\EditTicketScanLog::route('/{record}/edit'),
        ];
    }
}
