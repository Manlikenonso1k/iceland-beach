<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Room;
use App\Services\BookingWorkflowService;
use App\Services\ReservationService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Mail;
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
                Select::make('room_name')
                    ->options(fn () => Room::query()->orderBy('room_name')->pluck('room_name', 'room_name'))
                    ->searchable()
                    ->required(),
                TextInput::make('room_category')->maxLength(255),
                TextInput::make('room_price')->numeric()->prefix('NGN'),
                Select::make('is_booked')
                    ->options([
                        'rejected' => 'Rejected',
                        'no' => 'Pending',
                        'booked' => 'Confirmed',
                        'expired' => 'Expired',
                    ])
                    ->required(),
                Textarea::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->rows(3)
                    ->maxLength(1000),
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
                TextColumn::make('guest_name')->label('Guest Name')->searchable(),
                TextColumn::make('guest_email')->label('Email')->searchable(),
                TextColumn::make('guest_phone')->label('Phone'),
                TextColumn::make('room_type')->label('Room'),
                TextColumn::make('check_in')->label('Check In')->dateTime(),
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
