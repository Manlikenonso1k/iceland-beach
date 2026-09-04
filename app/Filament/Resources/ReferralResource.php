<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralResource\Pages;
use App\Models\Referral;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class ReferralResource extends Resource
{

    /**
     * Staff-only. Referral partners use their own guard and panel; this makes
     * that explicit per resource rather than relying on panel access alone.
     */
    public static function canAccess(): bool
    {
        return auth()->guard('web')->check();
    }
    protected static ?string $model = Referral::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Events';

    protected static ?string $navigationLabel = 'Referrals';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Partner name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Sign-in email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('The partner signs in at /referral with this.'),

            TextInput::make('phone')
                ->tel()
                ->maxLength(50),

            TextInput::make('password')
                ->password()
                ->revealable()
                ->minLength(8)
                // Required on create; blank on edit keeps the existing password.
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText('Leave blank when editing to keep the current password.'),

            TextInput::make('code')
                ->label('Referral code')
                ->required()
                ->maxLength(32)
                ->unique(ignoreRecord: true)
                // Auto-filled on create; staff may still override it.
                ->default(fn () => Referral::generateCode())
                ->helperText('Auto-generated. Guests type this at checkout.')
                ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state))),

            TextInput::make('commission_rate')
                ->label('Commission rate')
                ->numeric()
                ->suffix('%')
                ->minValue(0)
                ->maxValue(100)
                ->default(10)
                ->required()
                ->helperText('Percentage of each ticket sale this partner earns. Changing it affects future sales only — past commission is snapshotted.'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive codes stop earning credit but keep past sales.'),

            Textarea::make('notes')->rows(3)->maxLength(1000),

            Placeholder::make('stats')
                ->label('Performance')
                ->content(fn (?Referral $record) => $record
                    ? sprintf(
                        '%d successful sale(s) — NGN %s gross, NGN %s commission earned',
                        $record->successfulSalesCount(),
                        number_format($record->totalAmount() / 100, 2),
                        number_format($record->totalCommission() / 100, 2),
                    )
                    : 'Available once saved.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->searchable()->copyable()->weight('bold'),
                TextColumn::make('name')->label('Partner')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('sales_count')
                    ->label('Successful sales')
                    ->counts(['sales' => fn (Builder $q) => $q->where('status', 'successful')])
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state): string => rtrim(rtrim((string) $state, '0'), '.') . '%')
                    ->sortable(),
                TextColumn::make('commission_earned')
                    ->label('Commission earned')
                    ->getStateUsing(fn (Referral $record): string => 'NGN ' . number_format($record->totalCommission() / 100, 2)),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('Never')
                    ->toggleable(),
                TextColumn::make('sales_sum_amount')
                    ->label('Total earned')
                    ->sum(['sales' => fn (Builder $q) => $q->where('status', 'successful')], 'amount')
                    ->formatStateUsing(fn ($state) => 'NGN ' . number_format(((int) $state) / 100, 2)),
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ReferralResource\RelationManagers\SalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit' => Pages\EditReferral::route('/{record}/edit'),
        ];
    }
}
