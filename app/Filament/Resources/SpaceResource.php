<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpaceResource\Pages;
use App\Models\ServiceBooking;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Spaces — the bookable venues shown at /services (nav label "Spaces").
 *
 * Backed by the existing `services` table via the ServiceBooking model. No new
 * table was created: the public ServicesPage already reads this data from
 * /api/services, so a separate `spaces` table would have split one catalogue
 * across two sources.
 *
 * Super Admin only, matching RoomResource — pricing is commercially sensitive.
 */
class SpaceResource extends Resource
{
    protected static ?string $model = ServiceBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Resort Operations';

    protected static ?string $navigationLabel = 'Spaces & Pricing';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'space';

    protected static ?string $pluralModelLabel = 'spaces';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('Super Admin');
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }

    /**
     * Whether the category currently typed into the form is the complimentary
     * one. Delegates to the model so the rule has a single definition — the
     * form is reading unsaved state, so there is no record to ask yet.
     */
    protected static function categoryIsComplimentary(mixed $category): bool
    {
        return (new ServiceBooking(['service_category' => (string) $category]))->isComplimentary();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Space')
                ->schema([
                    TextInput::make('service_name')
                        ->label('Space name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('service_category')
                        ->label('Category')
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->helperText('Use "' . ServiceBooking::COMPLIMENTARY_CATEGORY . '" for spaces offered at no charge — they show as Free instead of a price.')
                        // Complimentary is unioned in so it is offered before
                        // any space uses it; the rest come from live data.
                        ->datalist(fn () => ServiceBooking::query()
                            ->whereNotNull('service_category')
                            ->distinct()
                            ->pluck('service_category')
                            ->push(ServiceBooking::COMPLIMENTARY_CATEGORY)
                            ->unique()
                            ->sort()
                            ->values()
                            ->all()),
                    Textarea::make('description')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    TextInput::make('capacity')
                        ->label('Capacity (guests)')
                        ->numeric()
                        ->minValue(0),
                ])
                ->columns(2),

            Section::make('Pricing')
                ->schema([
                    TextInput::make('service_price')
                        ->label(fn (Get $get): string => static::categoryIsComplimentary($get('service_category'))
                            ? 'Price (not charged)'
                            : 'Price from')
                        ->numeric()
                        ->prefix('₦')
                        ->minValue(0)
                        // Required for a priced category, because a 0.00 there
                        // is a space nobody filled in rather than a free one.
                        // A Complimentary space may be left blank and stores 0,
                        // so the legacy non-null column stays satisfied.
                        ->required(fn (Get $get): bool => ! static::categoryIsComplimentary($get('service_category')))
                        ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                        ->helperText(fn (Get $get): ?string => static::categoryIsComplimentary($get('service_category'))
                            ? 'Complimentary spaces are never charged. Leave this blank.'
                            : null),
                ]),

            Section::make('Images')
                ->description('Uploads take precedence over the legacy image path below.')
                ->schema([
                    FileUpload::make('main_image')
                        ->label('Main image')
                        ->image()
                        ->disk('public')
                        ->directory('spaces/main')
                        ->imageEditor()
                        ->maxSize(5120),

                    FileUpload::make('gallery_images')
                        ->label('Gallery images')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->disk('public')
                        ->directory('spaces/gallery')
                        ->maxFiles(10)
                        ->maxSize(5120),

                    TextInput::make('service_image')
                        ->label('Legacy image path')
                        ->maxLength(255)
                        ->helperText('Original public path. Used only when no main image is uploaded.')
                        ->dehydrateStateUsing(fn (?string $state) => $state === null ? null : trim($state)),

                    Placeholder::make('current_image')
                        ->label('Currently showing')
                        ->content(fn (?ServiceBooking $record) => $record?->displayImage() ?? 'No image set.'),
                ])
                ->columns(1),

            Section::make('Availability')
                ->schema([
                    Select::make('is_booked')
                        ->label('Status')
                        ->options([
                            'no' => 'Available',
                            'booked' => 'Booked',
                            'expired' => 'Expired',
                        ])
                        ->required(),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('main_image')
                    ->label('Photo')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(fn (ServiceBooking $record) => $record->displayImage()),
                TextColumn::make('service_name')->label('Space')->searchable()->sortable(),
                TextColumn::make('service_category')->label('Category')->searchable()->sortable()->toggleable(),
                TextColumn::make('capacity')->label('Capacity')->placeholder('—')->sortable()->toggleable(),
                TextColumn::make('service_price')
                    ->label('From')
                    // priceLabel() lives on the model so the admin table,
                    // the public grid, the booking form and the report all
                    // render the same thing.
                    ->formatStateUsing(fn ($state, ServiceBooking $record): string => $record->priceLabel())
                    ->badge(fn (ServiceBooking $record): bool => $record->isFree())
                    ->color(fn (ServiceBooking $record): ?string => $record->isFree() ? 'success' : null)
                    ->sortable(),
                TextColumn::make('is_booked')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'booked' => 'success',
                        'expired' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('service_category')
                    ->label('Category')
                    ->options(fn () => ServiceBooking::query()
                        ->whereNotNull('service_category')
                        ->distinct()
                        ->pluck('service_category', 'service_category')
                        ->put(ServiceBooking::COMPLIMENTARY_CATEGORY, ServiceBooking::COMPLIMENTARY_CATEGORY)
                        ->sort()
                        ->all()),
            ])
            ->defaultSort('service_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpaces::route('/'),
            'create' => Pages\CreateSpace::route('/create'),
            'edit' => Pages\EditSpace::route('/{record}/edit'),
        ];
    }
}
