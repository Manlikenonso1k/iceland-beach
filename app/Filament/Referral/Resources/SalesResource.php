<?php

namespace App\Filament\Referral\Resources;

use App\Filament\Referral\Resources\SalesResource\Pages;
use App\Models\ReferralTicketSale;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The partner's own sales. Read-only.
 *
 * Scoping lives in getEloquentQuery() so it applies to every query the resource
 * makes — list, view and any direct record lookup — meaning a partner cannot
 * reach another partner's row by editing the URL.
 *
 * The guard returns null defensively: if it ever did, whereRaw('1 = 0') returns
 * nothing rather than every sale in the system.
 */
class SalesResource extends Resource
{
    protected static ?string $model = ReferralTicketSale::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'My sales';

    protected static ?string $modelLabel = 'sale';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $referralId = auth('referral')->id();

        return parent::getEloquentQuery()
            ->when($referralId, fn (Builder $q) => $q->where('referral_id', $referralId))
            ->when(! $referralId, fn (Builder $q) => $q->whereRaw('1 = 0'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('buyer_name')
                    ->label('Buyer')
                    ->searchable()
                    ->description(fn (ReferralTicketSale $record): ?string => $record->buyer_email)
                    ->placeholder('—'),
                TextColumn::make('quantity')
                    ->label('Qty'),
                // Stored in kobo.
                TextColumn::make('amount')
                    ->label('Ticket value')
                    ->formatStateUsing(fn ($state): string => '₦' . number_format(((int) $state) / 100, 2))
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->label('Your commission')
                    ->formatStateUsing(fn ($state): string => '₦' . number_format(((int) $state) / 100, 2))
                    ->description(fn (ReferralTicketSale $record): string => rtrim(rtrim((string) $record->commission_rate, '0'), '.') . '%')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color('success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No sales yet')
            ->emptyStateDescription('Share your referral code with customers to start earning.')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
        ];
    }
}
