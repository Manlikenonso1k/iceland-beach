<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralResource\Pages;
use App\Models\Referral;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
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
    protected static ?string $model = Referral::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Events';

    protected static ?string $navigationLabel = 'Referrals';

    /**
     * Referral partners see only their own row; staff see everything.
     *
     * Scoping here (rather than in the page) means it applies to every query the
     * resource makes — list, view, edit and any direct record lookup — so a
     * partner cannot reach another partner's record by guessing a URL.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if ($user instanceof User && $user->isReferralPartner()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    /** Only staff may create, edit or delete referral accounts. */
    public static function canCreate(): bool
    {
        return ! static::currentUserIsPartner();
    }

    public static function canEdit($record): bool
    {
        return ! static::currentUserIsPartner();
    }

    public static function canDelete($record): bool
    {
        return ! static::currentUserIsPartner();
    }

    private static function currentUserIsPartner(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isReferralPartner();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('user_id')
                ->label('Referral account')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Pick an existing user, or create one below.')
                ->createOptionForm([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')->email()->required()->unique('users', 'email'),
                    TextInput::make('password')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->helperText('The partner signs in to /admin with this.'),
                ])
                ->createOptionUsing(function (array $data): int {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => $data['password'], // 'hashed' cast handles it
                    ]);
                    $user->assignRole('Referral');

                    return $user->id;
                }),

            TextInput::make('code')
                ->label('Referral code')
                ->required()
                ->maxLength(32)
                ->unique(ignoreRecord: true)
                // Auto-filled on create; staff may still override it.
                ->default(fn () => Referral::generateCode())
                ->helperText('Auto-generated. Guests type this at checkout.')
                ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state))),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive codes stop earning credit but keep past sales.'),

            Textarea::make('notes')->rows(3)->maxLength(1000),

            Placeholder::make('stats')
                ->label('Performance')
                ->content(fn (?Referral $record) => $record
                    ? sprintf(
                        '%d successful sale(s) — NGN %s',
                        $record->successfulSalesCount(),
                        number_format($record->totalAmount() / 100, 2),
                    )
                    : 'Available once saved.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->searchable()->copyable()->weight('bold'),
                TextColumn::make('user.name')->label('Partner')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable()->toggleable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('sales_count')
                    ->label('Successful sales')
                    ->counts(['sales' => fn (Builder $q) => $q->where('status', 'successful')])
                    ->sortable(),
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
