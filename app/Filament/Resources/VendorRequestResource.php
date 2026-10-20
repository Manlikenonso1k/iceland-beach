<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorRequestResource\Pages;
use App\Mail\VendorApprovalMail;
use App\Models\VendorRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class VendorRequestResource extends Resource
{
    use \App\Filament\Concerns\RestrictsManagementAreas;


    /**
     * Staff-only. Referral partners use their own guard and panel; this makes
     * that explicit per resource rather than relying on panel access alone.
     */
    public static function canAccess(): bool
    {
        return static::canViewAny();
    }
    protected static ?string $model = VendorRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Events';

    protected static ?string $navigationLabel = 'Vendor Requests';

    protected static ?int $navigationSort = 3;

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendorSpot.name')
                    ->label('Spot')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Applicant Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('expected_to_sell')
                    ->label('Intends to Sell')
                    ->limit(50)
                    ->tooltip(fn (VendorRequest $record) => $record->expected_to_sell),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'info',
                        'paid'     => 'success',
                        default    => 'gray',
                    })
                    ->label('Status'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->label('Submitted'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'paid'     => 'Paid',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                // ── Approve & Send Payment Link ──────────────────────────────
                Action::make('approve')
                    ->label('Approve & Send Payment Link')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Vendor Request')
                    ->modalDescription(fn (VendorRequest $record) =>
                        "Approve {$record->name}'s request for {$record->vendorSpot->name}? " .
                        "An email with a payment link will be sent to {$record->email}."
                    )
                    ->action(function (VendorRequest $record) {
                        // Update request status
                        $record->update(['status' => 'approved']);

                        // Send approval email with payment link
                        Mail::to($record->email)
                            ->send(new VendorApprovalMail($record));

                        Notification::make()
                            ->success()
                            ->title('Request approved')
                            ->body("Approval email sent to {$record->email}")
                            ->send();
                    })
                    ->hidden(fn (VendorRequest $record) => $record->status !== 'pending'),

                ViewAction::make(),
            ])
            ->bulkActions([])
            ->poll('30s');
    }

    // ── Form (view only) ──────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->disabled()->label('Applicant Name'),
                TextInput::make('email')->disabled(),
                TextInput::make('address')->disabled(),
                Textarea::make('expected_to_sell')->disabled()->label('Intends to Sell'),
                Select::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'paid'     => 'Paid',
                    ])
                    ->disabled(),
                TextInput::make('vendorSpot.name')->disabled()->label('Spot'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorRequests::route('/'),
            'view'  => Pages\ViewVendorRequest::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // requests are created via the public map only
    }
}
