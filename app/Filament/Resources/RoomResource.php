<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Room pricing and imagery.
 *
 * Previously these could only be changed directly in the database — there was
 * no Room resource in the panel at all.
 *
 * Restricted to Super Admin: rates and room photos are commercially sensitive,
 * and every other role that can reach the panel (Front Desk, Scanner, Referral,
 * Manager) has no reason to reprice inventory.
 */
class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Resort Operations';

    protected static ?string $navigationLabel = 'Rooms & Pricing';

    protected static ?int $navigationSort = 1;

    /**
     * Gates the nav item, the routes and every record action.
     *
     * The role is 'Super Admin' — the label used throughout this project's
     * Spatie setup — not the 'super_admin' slug form.
     */
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Room')
                ->schema([
                    TextInput::make('room_name')
                        ->label('Room name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('room_category')
                        ->label('Category')
                        ->maxLength(255)
                        ->datalist(fn () => Room::query()
                            ->whereNotNull('room_category')
                            ->distinct()
                            ->pluck('room_category')
                            ->all()),
                ])
                ->columns(2),

            Section::make('Pricing')
                ->schema([
                    TextInput::make('room_price')
                        ->label('Price per night')
                        ->numeric()
                        ->prefix('₦')
                        ->minValue(0)
                        ->required(),
                ]),

            Section::make('Room images')
                ->description('Uploads are stored on the public disk and take precedence over the legacy image path below.')
                ->schema([
                    FileUpload::make('main_image')
                        ->label('Main image')
                        ->image()
                        ->disk('public')
                        ->directory('rooms/main')
                        ->imageEditor()
                        ->maxSize(5120)
                        ->helperText('Shown as the room’s primary photo. Max 5 MB.'),

                    FileUpload::make('gallery_images')
                        ->label('Gallery images')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->disk('public')
                        ->directory('rooms/gallery')
                        ->maxFiles(10)
                        ->maxSize(5120)
                        ->helperText('Up to 10 additional photos. Drag to reorder.'),

                    TextInput::make('room_image')
                        ->label('Legacy image path')
                        ->maxLength(255)
                        ->helperText('Original public path, e.g. /legacy-images/rooms/Gold Room 1.jpeg. Used only when no main image is uploaded.')
                        ->dehydrateStateUsing(fn (?string $state) => $state === null ? null : trim($state)),

                    Placeholder::make('current_image')
                        ->label('Currently showing')
                        ->content(fn (?Room $record) => $record?->displayImage() ?? 'No image set.'),
                ])
                ->columns(1),

            Section::make('Availability')
                ->description('Reservation state is normally driven by bookings — change it here only to correct data.')
                ->schema([
                    Select::make('is_booked')
                        ->label('Status')
                        ->options([
                            'no' => 'Available',
                            'booked' => 'Booked',
                            'rejected' => 'Rejected',
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
                    ->defaultImageUrl(fn (Room $record) => $record->displayImage()),
                TextColumn::make('room_name')->label('Room')->searchable()->sortable(),
                TextColumn::make('room_category')->label('Category')->searchable()->sortable()->toggleable(),
                TextColumn::make('room_price')
                    ->label('Per night')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('gallery_images')
                    ->label('Gallery')
                    ->formatStateUsing(fn ($state): string => (string) count((array) $state))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('is_booked')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'booked' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('room_category')
                    ->label('Category')
                    ->options(fn () => Room::query()
                        ->whereNotNull('room_category')
                        ->distinct()
                        ->pluck('room_category', 'room_category')
                        ->all()),
            ])
            ->defaultSort('room_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
