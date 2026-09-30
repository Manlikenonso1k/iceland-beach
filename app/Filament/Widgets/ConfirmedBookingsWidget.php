<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Services\BookingWorkflowService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ConfirmedBookingsWidget extends BaseWidget
{
    /** Registered explicitly, never auto-discovered — see BookingCalendarWidget. */
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Confirmed Bookings';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with('room')
                    ->where('status', 'confirmed')
                    ->orderByDesc('check_in')
            )
            ->emptyStateHeading('No confirmed bookings yet')
            ->emptyStateDescription('Bookings appear here once staff confirm them.')
            ->columns([
                TextColumn::make('id')
                    ->label('Ref')
                    ->formatStateUsing(fn ($state): string => '#' . str_pad((string) $state, 6, '0', STR_PAD_LEFT))
                    ->sortable(),
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->searchable(['guest_name', 'guest_phone', 'guest_email'])
                    ->weight('medium')
                    // Phone is the identifier for a booking taken by telephone.
                    ->description(fn (Booking $record): string => $record->guest_phone ?: 'No phone'),
                TextColumn::make('guest_phone')
                    ->label('Phone')
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->placeholder('No phone')
                    ->toggleable(),
                TextColumn::make('guest_email')
                    ->label('Email')
                    ->placeholder('No email')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // room_type is the stored label; the relation covers rooms renamed since.
                TextColumn::make('room_type')
                    ->label('Room')
                    ->formatStateUsing(fn ($state, Booking $record): string => $record->room?->room_name ?: (string) $state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('check_in')
                    ->label('Check-in')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('Check-out')
                    ->date('M j, Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('success'),
            ])
            ->actions([
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel this booking?')
                    ->modalDescription('The room is released and the guest is emailed.')
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason (optional)')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(fn (Booking $record): bool => $this->canManage($record))
                    // Same service the pending widget and BookingResource use, so a
                    // cancellation releases the room and emails the guest identically.
                    ->action(function (Booking $record, array $data, BookingWorkflowService $bookingWorkflow): void {
                        $bookingWorkflow->rejectBooking($record, $data['reason'] ?? null);

                        Notification::make()
                            ->title('Booking cancelled')
                            ->body('Room released and the guest has been notified.')
                            ->success()
                            ->send();

                        $this->dispatch('refresh');
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25]);
    }

    private function canManage(Booking $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        return $record->status === 'confirmed'
            && $user !== null
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Super Admin', 'Manager']);
    }
}
