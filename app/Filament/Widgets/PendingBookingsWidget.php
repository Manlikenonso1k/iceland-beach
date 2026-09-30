<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Services\BookingWorkflowService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
                    // Searches the two identifiers as well as the name, so a
                    // guest who phoned in can be found by their number.
                    ->searchable(['guest_name', 'guest_phone', 'guest_email'])
                    ->weight('medium')
                    // Phone, not email: a booking taken over the telephone has
                    // no email at all, and the phone is what staff call back on.
                    ->description(fn (Booking $record): string => $record->guest_phone ?: 'No phone')
                    ->limit(24),
                TextColumn::make('guest_email')
                    ->label('Email')
                    ->placeholder('No email')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
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
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('mass_confirm')
                        ->label('Confirm selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm selected bookings?')
                        ->modalDescription('Each room is held and every guest is emailed.')
                        ->modalSubmitActionLabel('Yes, confirm all')
                        ->action(fn (Collection $records, BookingWorkflowService $workflow) => $this->runBulk(
                            $records,
                            fn (Booking $b) => $workflow->confirmBooking($b),
                            'confirmed',
                        ))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mass_reject')
                        ->label('Reject selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject selected bookings?')
                        ->modalDescription('Each room is released and every guest is emailed.')
                        ->modalSubmitActionLabel('Yes, reject all')
                        ->form([
                            Textarea::make('reason')
                                ->label('Reason (optional)')
                                ->helperText('Included in every guest email.')
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->action(fn (Collection $records, array $data, BookingWorkflowService $workflow) => $this->runBulk(
                            $records,
                            fn (Booking $b) => $workflow->rejectBooking($b, $data['reason'] ?? null),
                            'rejected',
                        ))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 20]);
    }

    /**
     * Apply an action to every selected booking and report once.
     *
     * Each record is isolated in its own try/catch: one failure — a bounced
     * email, say — must not abort the rest of the batch, and the operator gets
     * a single summary rather than one toast per row.
     *
     * @param  Collection<int, Booking>  $records
     */
    private function runBulk(Collection $records, callable $apply, string $verb): void
    {
        // Authorisation lives here rather than in visible(): a bulk action's
        // visible() closure is evaluated without reliable auth context and
        // resolved false even for a Super Admin, which hid the row checkboxes.
        if (! self::operatorCanManage()) {
            Notification::make()
                ->title('Not permitted')
                ->body('Only a Super Admin or Manager can action bookings in bulk.')
                ->danger()
                ->send();

            return;
        }

        $done = 0;
        $failed = 0;

        foreach ($records as $record) {
            // Skip anything already actioned — a stale selection must not
            // re-email a guest.
            if ($record->status !== 'pending') {
                continue;
            }

            try {
                $apply($record);
                $done++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error("Bulk {$verb} failed for booking {$record->id}: " . $e->getMessage());
            }
        }

        Notification::make()
            ->title(ucfirst($verb) . ' ' . $done . ' ' . str('booking')->plural($done))
            ->body($failed > 0
                ? $failed . ' could not be processed — see the log.'
                : 'Guests have been emailed.')
            ->color($failed > 0 ? 'warning' : 'success')
            ->send();

        $this->dispatch('refresh');
    }

    /**
     * Row actions gate per record; bulk actions gate on the operator alone.
     *
     * Static because $this is not reliably bound inside Filament action
     * closures — an unbound call there silently returns false, which hides the
     * whole bulk group and with it the row checkboxes.
     */
    private static function operatorCanManage(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Super Admin', 'Manager']);
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
