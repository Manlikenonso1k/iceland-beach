<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsSpaceBookingWrites;
use App\Filament\Resources\SpaceBookingResource\Pages;
use App\Models\ServiceBooking;
use App\Models\SpaceBooking;
use App\Services\SpaceBookingReportService;
use App\Services\SpaceBookingWorkflowService;
use Filament\Actions\Action as PageAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Space (venue) bookings — the counterpart of BookingResource for rooms.
 *
 * Intentionally the same shape as BookingResource: a calendar at the index, a
 * table view at /list, create and edit pages, confirm/deny actions that
 * delegate to a workflow service, and a daily/weekly/monthly PDF report.
 *
 * Separate from SpaceResource, which stays what it already was: the
 * Super-Admin-only catalogue and pricing screen. This resource is the ledger.
 */
class SpaceBookingResource extends Resource
{
    // Space Host writes; Front Desk sees but cannot change. Viewing is NOT
    // gated by the trait, which is what makes the cross-view read-only.
    use RestrictsSpaceBookingWrites;

    protected static ?string $model = SpaceBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Resort Operations';

    protected static ?string $navigationLabel = 'Space Bookings';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'space booking';

    protected static ?string $pluralModelLabel = 'space bookings';

    protected static ?string $recordTitleAttribute = 'guest_name';

    /**
     * Any staff member may reach this area. Referral partners authenticate on
     * their own guard and must never see it.
     */
    public static function canAccess(): bool
    {
        return auth()->guard('web')->check();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['guest_name', 'guest_phone', 'guest_email', 'space_name'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('space_id')
                ->label('Space')
                ->options(fn () => ServiceBooking::query()->orderBy('service_name')->pluck('service_name', 'id'))
                ->searchable()
                ->required()
                // space_name is the human-readable snapshot kept beside the id,
                // mirroring bookings.room_type.
                ->afterStateUpdated(fn ($state, callable $set) => $set(
                    'space_name',
                    ServiceBooking::find($state)?->service_name,
                ))
                ->live(),

            TextInput::make('space_name')->label('Space name')->maxLength(255),

            TextInput::make('guest_name')->required()->maxLength(255),

            // Phone required, email optional — the same decision already made
            // for the room form: staff take bookings by phone, where no email
            // address exists.
            TextInput::make('guest_phone')
                ->label('Phone number')
                ->tel()
                ->required()
                ->maxLength(50)
                ->helperText('Primary contact. Used to identify the guest.'),

            TextInput::make('guest_email')
                ->label('Email (optional)')
                ->email()
                ->maxLength(255)
                ->helperText('If provided, confirmation and decline emails are sent here.'),

            TextInput::make('guests')
                ->label('Number of people')
                ->numeric()
                ->minValue(1)
                ->maxValue(1000),

            DateTimePicker::make('check_in')->label('From')->seconds(false)->required(),
            DateTimePicker::make('check_out')->label('To')->seconds(false)->after('check_in'),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'rejected' => 'Rejected',
                ])
                ->default('pending')
                ->required(),

            Textarea::make('rejection_reason')
                ->label('Rejection reason')
                ->rows(3)
                ->maxLength(1000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guest_name')->label('Guest')->searchable(),
                TextColumn::make('guest_phone')
                    ->label('Phone')
                    ->icon('heroicon-o-phone')
                    ->searchable()
                    ->copyable()
                    ->placeholder('No phone'),
                TextColumn::make('guest_email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('No email')
                    ->toggleable(),
                TextColumn::make('space_name')
                    ->label('Space')
                    ->formatStateUsing(fn (?string $state, SpaceBooking $record): string => $record->space?->service_name ?: ((string) $state ?: '—'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('guests')->label('People')->placeholder('—'),
                TextColumn::make('check_in')->label('From')->dateTime()->sortable(),
                TextColumn::make('check_out')->label('To')->dateTime()->placeholder('—'),
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
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    // A default arm is required: the payment flow adds
                    // pending_payment and payment_failed, and an unmatched value
                    // would throw UnhandledMatchError and break the table.
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
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'rejected' => 'Rejected',
                    'pending_payment' => 'Awaiting payment',
                    'payment_failed' => 'Payment failed',
                ]),
                SelectFilter::make('space_id')
                    ->label('Space')
                    ->options(fn () => ServiceBooking::query()->orderBy('service_name')->pluck('service_name', 'id')),
            ])
            ->actions([
                // Both this and PendingSpaceBookingsWidget delegate to
                // SpaceBookingWorkflowService, so status, the catalogue mirror,
                // the activity log and the guest email cannot drift apart.
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SpaceBooking $record): bool => $record->status === 'pending' && static::canWrite())
                    ->action(function (SpaceBooking $record, SpaceBookingWorkflowService $workflow): void {
                        // visible() hides a button; it does not stop a crafted
                        // Livewire call. The real gate is here.
                        abort_unless(static::canWrite(), 403);

                        $workflow->confirmBooking($record);

                        Notification::make()
                            ->title('Space booking confirmed')
                            ->body('Space held and confirmation email sent.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Deny')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejection_reason')->label('Reason')->required(),
                    ])
                    ->visible(fn (SpaceBooking $record): bool => $record->status === 'pending' && static::canWrite())
                    ->action(function (SpaceBooking $record, array $data, SpaceBookingWorkflowService $workflow): void {
                        abort_unless(static::canWrite(), 403);

                        $workflow->rejectBooking($record, $data['rejection_reason']);

                        Notification::make()
                            ->title('Space booking denied')
                            ->body('Space released and decline email sent.')
                            ->success()
                            ->send();
                    }),
                EditAction::make()->visible(fn (): bool => static::canWrite()),
                DeleteAction::make()->visible(fn (): bool => static::canWrite()),
            ])
            ->defaultSort('check_in', 'desc');
    }

    /**
     * Daily / weekly / monthly PDF report. Identical form to
     * BookingResource::downloadReportAction() so the two feel the same.
     *
     * Available to anyone who can see the area, including read-only Front Desk:
     * host.md asks for cross-visibility of reports, and a report writes nothing.
     */
    public static function downloadReportAction(): PageAction
    {
        return PageAction::make('downloadSpaceReport')
            ->label('Download report')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading('Download space booking report')
            ->modalSubmitActionLabel('Download PDF')
            ->form([
                Select::make('period')
                    ->label('Period type')
                    ->options(SpaceBookingReportService::PERIODS)
                    ->default('daily')
                    ->required()
                    ->live(),
                DatePicker::make('date')
                    ->label(fn (Get $get): string => $get('period') === 'weekly' ? 'Any day in the week (Mon–Sun)' : 'Date')
                    ->default(now())
                    ->native(false)
                    ->visible(fn (Get $get): bool => in_array($get('period'), ['daily', 'weekly'], true))
                    ->required(),
                Select::make('month')
                    ->options(collect(range(1, 12))->mapWithKeys(fn (int $m): array => [$m => now()->month($m)->startOfMonth()->format('F')])->all())
                    ->default(now()->month)
                    ->visible(fn (Get $get): bool => $get('period') === 'monthly')
                    ->required(),
                Select::make('year')
                    ->options(collect(range(now()->year + 1, now()->year - 5))->mapWithKeys(fn (int $y): array => [$y => (string) $y])->all())
                    ->default(now()->year)
                    ->visible(fn (Get $get): bool => $get('period') === 'monthly')
                    ->required(),
            ])
            ->action(fn (array $data) => app(SpaceBookingReportService::class)->download($data));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            // Calendar at the index, table at /list — the same arrangement as
            // BookingResource, where 'index' is BookingCalendar.
            'index' => Pages\SpaceBookingCalendar::route('/'),
            'list' => Pages\ListSpaceBookings::route('/list'),
            'create' => Pages\CreateSpaceBooking::route('/create'),
            'edit' => Pages\EditSpaceBooking::route('/{record}/edit'),
        ];
    }
}
