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
                TextColumn::make('room_name')->searchable()->sortable(),
                TextColumn::make('room_category')->searchable()->toggleable(),
                TextColumn::make('is_booked')
                    ->badge()
                    ->colors([
                        'warning' => 'no',
                        'success' => 'booked',
                        'gray' => 'rejected',
                        'danger' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'no' => 'Pending',
                        'booked' => 'Confirmed',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                        default => ucfirst($state),
                    }),
                TextColumn::make('customer_name')->searchable()->placeholder('-'),
                TextColumn::make('email')->searchable()->placeholder('-'),
                TextColumn::make('start_date')->dateTime('Y-m-d H:i')->placeholder('-'),
                TextColumn::make('end_date')->dateTime('Y-m-d H:i')->placeholder('-'),
                TextColumn::make('rejection_reason')->label('Reason')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_booked')
                    ->options([
                        'rejected' => 'Rejected',
                        'no' => 'Pending',
                        'booked' => 'Confirmed',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Action::make('confirmBooking')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (Room $record): bool {
                        /** @var \App\Models\User|null $user */
                        $user = Filament::auth()->user();

                        return $record->is_booked === 'no'
                            && $user !== null
                            && method_exists($user, 'hasAnyRole')
                            && $user->hasAnyRole(['Super Admin', 'Manager']);
                    })
                    ->action(function (Room $record, BookingWorkflowService $bookingWorkflow): void {
                        $bookingWorkflow->confirm($record);

                        Notification::make()
                            ->title('Booking confirmed')
                            ->body('Status updated and confirmation email processed.')
                            ->success()
                            ->send();
                    }),
                Action::make('rejectBooking')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason (optional)')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(function (Room $record): bool {
                        /** @var \App\Models\User|null $user */
                        $user = Filament::auth()->user();

                        return $record->is_booked === 'no'
                            && $user !== null
                            && method_exists($user, 'hasAnyRole')
                            && $user->hasAnyRole(['Super Admin', 'Manager']);
                    })
                    ->action(function (Room $record, array $data, BookingWorkflowService $bookingWorkflow): void {
                        $reason = isset($data['reason']) ? trim((string) $data['reason']) : null;
                        $bookingWorkflow->reject($record, $reason);

                        Notification::make()
                            ->title('Booking rejected')
                            ->body('Status updated and decline email processed.')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\BookingCalendar::route('/'),
            'list' => Pages\ListBookings::route('/list'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
