<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingWorkflowService;
use App\Services\ReservationService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Mail;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Resort Operations';

    protected static ?string $navigationLabel = 'Bookings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('room_id')
                    ->label('Room')
                    ->options(fn () => Room::query()->orderBy('room_name')->pluck('room_name', 'id'))
                    ->searchable()
                    ->required()
                    // room_type is the human-readable snapshot kept alongside the FK.
                    ->afterStateUpdated(fn ($state, callable $set) => $set(
                        'room_type',
                        Room::find($state)?->room_name,
                    ))
                    ->live(),
                TextInput::make('room_type')->label('Room name')->maxLength(255),
                TextInput::make('guest_name')->required()->maxLength(255),
                TextInput::make('guest_email')->email()->required()->maxLength(255),
                TextInput::make('guest_phone')->tel()->maxLength(50),
                Toggle::make('email_opt_in')
                    ->label('Subscribed to marketing email')
                    ->helperText('Guest ticked the opt-in box on the booking form.'),
                DateTimePicker::make('check_in')->seconds(false)->required(),
                DateTimePicker::make('check_out')->seconds(false)
                    ->after('check_in'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->required(),
                Textarea::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->rows(3)
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guest_name')->label('Guest Name')->searchable(),
                TextColumn::make('guest_email')->label('Email')->searchable(),
                TextColumn::make('guest_phone')->label('Phone'),
                IconColumn::make('email_opt_in')->label('Opted in')->boolean()->sortable(),
                TextColumn::make('room_type')->label('Room'),
                TextColumn::make('check_in')->label('Check In')->dateTime(),
                TextColumn::make('check_out')->label('Check Out')->dateTime(),
                TextColumn::make('status')->label('Status')->badge()->color(fn($state) => match($state) {
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'rejected' => 'danger',
                }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->status = 'confirmed';
                        $record->save();

                        // Confirming is what actually holds the room. The calendar
                        // widget reads the room record, so keep it in step.
                        $record->room?->forceFill([
                            'is_booked' => 'booked',
                            'customer_name' => $record->guest_name,
                            'email' => $record->guest_email,
                            'start_date' => $record->check_in,
                            'end_date' => $record->check_out,
                        ])->save();

                        Mail::to($record->guest_email)->send(new \App\Mail\BookingConfirmedMail($record));
                    })
                    ->color('success'),
                Action::make('reject')
                    ->label('Reject')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Textarea::make('rejection_reason')->label('Reason')->required(),
                    ])
                    ->action(function ($record, $data) {
                        $record->status = 'rejected';
                        $record->rejection_reason = $data['rejection_reason'];
                        $record->save();

                        // Releasing the room again so a rejection never leaves it held.
                        $record->room?->forceFill(['is_booked' => 'no'])->save();
                        Mail::to($record->guest_email)->send(new \App\Mail\BookingDeclinedMail($record, $data['rejection_reason']));
                    })
                    ->color('danger'),
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => Pages\BookingCalendar::route('/'),
            'list' => Pages\ListBookings::route('/list'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
