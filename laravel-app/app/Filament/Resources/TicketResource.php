<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Events';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?int $navigationSort = 1;

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Customer Name'),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->label('Email'),

                TextColumn::make('ticket_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'VIP'     => 'warning',
                        'Regular' => 'info',
                        default   => 'gray',
                    })
                    ->label('Type'),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->alignCenter(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => '₦' . number_format($state / 100, 2))
                    ->sortable(),

                IconColumn::make('paid')
                    ->boolean()
                    ->label('Paid')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('checked_in')
                    ->boolean()
                    ->label('Checked In')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('payment_gateway')
                    ->badge()
                    ->label('Gateway'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->label('Purchased'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('paid')
                    ->options([1 => 'Paid', 0 => 'Unpaid'])
                    ->label('Payment Status'),

                SelectFilter::make('checked_in')
                    ->options([1 => 'Checked In', 0 => 'Not Checked In'])
                    ->label('Check-in Status'),

                SelectFilter::make('ticket_type')
                    ->options(['Regular' => 'Regular', 'VIP' => 'VIP'])
                    ->label('Ticket Type'),

                SelectFilter::make('payment_gateway')
                    ->options(['paystack' => 'Paystack', 'titan' => 'TGI Titan'])
                    ->label('Gateway'),
            ])
            ->actions([
                // Manual check-in action (admin can also check in from dashboard)
                Action::make('check_in')
                    ->label('Check In')
                    ->icon('heroicon-o-qr-code')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Check-In')
                    ->modalDescription(fn (Ticket $record) => "Mark {$record->name} as checked in?")
                    ->action(fn (Ticket $record) => $record->update(['checked_in' => true]))
                    ->hidden(fn (Ticket $record) => $record->checked_in || !$record->paid)
                    ->successNotification(
                        Notification::make()->success()->title('Guest checked in successfully.')
                    ),

                ViewAction::make(),
            ])
            ->bulkActions([])
            ->poll('30s'); // auto-refresh every 30s for gate operators
    }

    // ── Form (view only — creation is handled by payment flow) ───────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->disabled(),
                TextInput::make('email')->disabled(),
                TextInput::make('phone')->disabled(),
                TextInput::make('ticket_type')->disabled(),
                TextInput::make('quantity')->disabled(),
                TextInput::make('amount')
                    ->disabled()
                    ->formatStateUsing(fn ($state) => '₦' . number_format($state / 100, 2))
                    ->label('Amount (Naira)'),
                TextInput::make('order_ref')->disabled()->label('Order Reference'),
                TextInput::make('payment_gateway')->disabled(),
                TextInput::make('payment_ref')->disabled()->label('Gateway Reference'),
                Select::make('paid')
                    ->options([1 => 'Paid', 0 => 'Unpaid'])
                    ->disabled(),
                Select::make('checked_in')
                    ->options([1 => 'Checked In', 0 => 'Not Checked In'])
                    ->disabled(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'view'  => Pages\ViewTicket::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // tickets are created by the payment flow only
    }
}
