<?php

namespace App\Filament\Widgets;

use App\Models\Room;
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
    protected static ?string $heading = 'Pending Bookings';

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 3,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Room::query()
                    ->where('is_booked', 'no')
                    ->whereNotNull('start_date')
                    ->orderBy('start_date')
            )
            ->columns([
                TextColumn::make('room_name')
                    ->label('Room')
                    ->searchable()
                    ->limit(20),
                TextColumn::make('customer_name')
                    ->label('Guest')
                    ->placeholder('-')
                    ->limit(20),
                TextColumn::make('start_date')
                    ->label('Check-in')
                    ->dateTime('M j, H:i'),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Room $record): bool => $this->canManage($record))
                    ->action(function (Room $record, BookingWorkflowService $bookingWorkflow): void {
                        $bookingWorkflow->confirm($record);

                        Notification::make()
                            ->title('Booking confirmed')
                            ->body('Status updated and confirmation email processed.')
                            ->success()
                            ->send();

                        $this->dispatch('refresh');
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason (optional)')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(fn (Room $record): bool => $this->canManage($record))
                    ->action(function (Room $record, array $data, BookingWorkflowService $bookingWorkflow): void {
                        $bookingWorkflow->reject($record, $data['reason'] ?? null);

                        Notification::make()
                            ->title('Booking rejected')
                            ->body('Status updated and decline email processed.')
                            ->success()
                            ->send();

                        $this->dispatch('refresh');
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 20]);
    }

    private function canManage(Room $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        return $record->is_booked === 'no'
            && $user !== null
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Super Admin', 'Manager']);
    }
}
