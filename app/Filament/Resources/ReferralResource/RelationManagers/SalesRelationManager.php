<?php

namespace App\Filament\Resources\ReferralResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only list of the ticket sales credited to a referral.
 *
 * Rows are written by TicketObserver on successful payment only, so there is
 * deliberately no create/edit/delete here — staff must not be able to fabricate
 * or amend a sale by hand.
 */
class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    protected static ?string $title = 'Successful ticket sales';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket.order_ref')->label('Order')->searchable(),
                TextColumn::make('buyer_name')
                    ->label('Buyer')
                    ->searchable()
                    ->description(fn ($record): ?string => $record->buyer_email)
                    // Older rows predate the buyer snapshot; fall back to the ticket.
                    ->formatStateUsing(fn ($state, $record): string => $state ?: ($record->ticket?->name ?: '—')),
                TextColumn::make('ticket.ticket_type')->label('Type'),
                TextColumn::make('quantity')->label('Qty'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => 'NGN ' . number_format(((int) $state) / 100, 2))
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state) => 'NGN ' . number_format(((int) $state) / 100, 2))
                    ->description(fn ($record): string => rtrim(rtrim((string) $record->commission_rate, '0'), '.') . '%')
                    ->sortable(),
                TextColumn::make('status')->badge()->color('success'),
                TextColumn::make('created_at')->label('Recorded')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
