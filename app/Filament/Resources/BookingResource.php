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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingResource extends Resource
{

    /**
     * Staff-only. Referral partners use their own guard and panel; this makes
     * that explicit per resource rather than relying on panel access alone.
     */
    public static function canAccess(): bool
    {
        return auth()->guard('web')->check();
    }
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
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Not required')
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('status')->label('Status')->badge()
                    // A default arm is required: the payment flow adds
                    // 'pending_payment' and 'payment_failed', and an unmatched
                    // value would throw UnhandledMatchError and break the table.
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state)))
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'rejected', 'payment_failed' => 'danger',
                        'pending_payment' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'rejected' => 'Rejected',
                        'pending_payment' => 'Awaiting payment',
                        'payment_failed' => 'Payment failed',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                // Both this and PendingBookingsWidget delegate to
                // BookingWorkflowService so status, room sync, activity log and
                // email stay identical wherever staff act on a booking.
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record): bool => $record->status === 'pending')
                    ->action(function (Booking $record, BookingWorkflowService $bookingWorkflow): void {
                        $bookingWorkflow->confirmBooking($record);

                        Notification::make()
                            ->title('Booking confirmed')
                            ->body('Room held and confirmation email sent.')
                            ->success()
                            ->send();
                    })
                    ->color('success'),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record): bool => $record->status === 'pending')
                    ->form([
                        Textarea::make('rejection_reason')->label('Reason')->required(),
                    ])
                    ->action(function (Booking $record, array $data, BookingWorkflowService $bookingWorkflow): void {
                        $bookingWorkflow->rejectBooking($record, $data['rejection_reason']);

                        Notification::make()
                            ->title('Booking rejected')
                            ->body('Room released and decline email sent.')
                            ->success()
                            ->send();
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
