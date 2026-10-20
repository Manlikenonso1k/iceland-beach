<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SpaceBookingResource;
use App\Models\SpaceBooking;
use App\Services\SpaceBookingWorkflowService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Space bookings awaiting review, with confirm and deny inline — the space
 * counterpart of PendingBookingsWidget.
 *
 * Authorisation defers to SpaceBookingResource::canWrite() so there is one
 * answer to "may this user change a space booking", shared with the resource
 * table and the custom actions. Front Desk therefore sees this widget (the
 * cross-view is read-only, not invisible) but gets no buttons.
 */
class PendingSpaceBookingsWidget extends BaseWidget
{
    /** Registered explicitly on SpaceBookingCalendar, never auto-discovered. */
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Pending Space Bookings';

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 3,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SpaceBooking::query()
                    ->with('space')
                    ->where('status', 'pending')
                    ->orderBy('check_in')
            )
            ->emptyStateHeading('No space bookings awaiting review')
            ->emptyStateDescription('New requests from the website appear here.')
            ->columns([
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->searchable(['guest_name', 'guest_phone', 'guest_email'])
                    ->weight('medium')
                    // Phone first: space bookings are frequently taken by phone,
                    // and guest_email is nullable.
                    ->description(fn (SpaceBooking $record): string => $record->guest_phone ?: 'No phone')
                    ->limit(24),
                TextColumn::make('space_name')
                    ->label('Space')
                    ->formatStateUsing(fn (?string $state, SpaceBooking $record): string => $record->space?->service_name ?: ((string) $state ?: '—'))
                    ->searchable()
                    ->limit(20),
                TextColumn::make('check_in')
                    ->label('From')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('To')
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
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SpaceBooking $record): bool => $record->status === 'pending'
                        && SpaceBookingResource::canWrite())
                    ->action(function (SpaceBooking $record, SpaceBookingWorkflowService $workflow): void {
                        // Re-checked server-side: visible() hides a button, it
                        // does not stop a crafted Livewire call.
                        abort_unless(SpaceBookingResource::canWrite(), 403);

                        $workflow->confirmBooking($record);

                        Notification::make()
                            ->title('Space booking confirmed')
                            ->body('Space held and confirmation email sent.')
                            ->success()
                            ->send();

                        $this->dispatch('refresh');
                    }),
                Action::make('reject')
                    ->label('Deny')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason (optional)')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(fn (SpaceBooking $record): bool => $record->status === 'pending'
                        && SpaceBookingResource::canWrite())
                    ->action(function (SpaceBooking $record, array $data, SpaceBookingWorkflowService $workflow): void {
                        abort_unless(SpaceBookingResource::canWrite(), 403);

                        $workflow->rejectBooking($record, $data['reason'] ?? null);

                        Notification::make()
                            ->title('Space booking denied')
                            ->body('Space released and decline email sent.')
                            ->success()
                            ->send();

                        $this->dispatch('refresh');
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 20]);
    }
}
