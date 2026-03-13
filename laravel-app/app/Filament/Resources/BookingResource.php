<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Room;
use App\Services\ReservationService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Resort Operations';

    protected static ?string $navigationLabel = 'Bookings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('room_name')->required()->maxLength(255),
                TextInput::make('room_category')->maxLength(255),
                TextInput::make('room_price')->numeric()->prefix('NGN'),
                Select::make('is_booked')
                    ->options([
                        'no' => 'Available',
                        'booked' => 'Booked',
                        'expired' => 'Expired',
                    ])
                    ->required(),
                TextInput::make('customer_name')->maxLength(255),
                TextInput::make('email')->email()->maxLength(255),
                DateTimePicker::make('start_date')->seconds(false),
                DateTimePicker::make('end_date')->seconds(false),
                TextInput::make('total_price')->numeric()->prefix('NGN'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room_name')->searchable()->sortable(),
                TextColumn::make('room_category')->searchable()->toggleable(),
                TextColumn::make('is_booked')
                    ->badge()
                    ->colors([
                        'success' => 'no',
                        'warning' => 'booked',
                        'danger' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('customer_name')->searchable()->placeholder('-'),
                TextColumn::make('email')->searchable()->placeholder('-'),
                TextColumn::make('start_date')->dateTime('Y-m-d H:i')->placeholder('-'),
                TextColumn::make('end_date')->dateTime('Y-m-d H:i')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('is_booked')
                    ->options([
                        'no' => 'Available',
                        'booked' => 'Booked',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Action::make('sendBookingMail')
                    ->label('Send Booking Mail')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn (Room $record) => ! empty($record->email) && $record->is_booked === 'booked')
                    ->action(function (Room $record, ReservationService $reservationService): void {
                        $reservationService->sendBookingEmails($record);

                        Notification::make()
                            ->title('Booking email sent')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
