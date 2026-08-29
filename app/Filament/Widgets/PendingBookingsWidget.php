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

class PendingBookingsWidget extends BaseWidget
{
    /**
     * Excluded from panel auto-discovery — see BookingCalendarWidget.
     * Registered explicitly on the Dashboard and in
     * BookingCalendar::getHeaderWidgets().
     */
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Pending Bookings';

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 3,
    ];

    public function table(Table $table): Table
    {
        return $table
            // Reads Booking, not Room. Previously this queried rooms with
            // is_booked='no' + dates, which only ever surfaced requests that had
            // been staged onto a room — bookings created any other way never
            // appeared, and the guest name came from the room's copy of it.
            ->query(
                Booking::query()
                    ->with('room')
                    ->where('status', 'pending')
                    ->orderBy('check_in')
            )
            ->emptyStateHeading('No bookings awaiting review')
            ->emptyStateDescription('New requests from the website appear here.')
            ->columns([
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Booking $record): ?string => $record->guest_email)
                    ->limit(24),
                TextColumn::make('room_type')
                    ->label('Room')
                    ->searchable()
                    ->limit(20),
                TextColumn::make('check_in')
                    ->label('Check-in')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('Check-out')
                    ->date('M j, Y')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record): bool => $this->canManage($record))
                    ->action(function (Booking $record, BookingWorkflowService $bookingWorkflow): void {
                        $bookingWorkflow->confirmBooking($record);

                        Notification::make()
                            ->title('Booking confirmed')
                            ->body('Room held and confirmation email sent.')
                            ->success()
                            ->send();

                        $this->dispatch('refresh');
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason (optional)')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(fn (Booking $record): bool => $this->canManage($record))
                    ->action(function (Booking $record, array $data, BookingWorkflowService $bookingWorkflow): void {
                        $bookingWorkflow->rejectBooking($record, $data['reason'] ?? null);

                        Notification::make()
                            ->title('Booking rejected')
                            ->body('Room released and decline email sent.')
                            ->success()
                            ->send();

                        $this->dispatch('refresh');
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 20]);
    }

    private function canManage(Booking $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        return $record->status === 'pending'
            && $user !== null
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Super Admin', 'Manager']);
    }
}
