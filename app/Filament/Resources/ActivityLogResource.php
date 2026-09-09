<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?int $navigationSort = 2;

    /** Audit trail: append-only, and visible only to Super Admin. */
    public static function canAccess(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();

        return $user instanceof \App\Models\User && $user->hasRole('Super Admin');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff member')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record): ?string => $record->user?->email)
                    ->placeholder('system'),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(70)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP address')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('device')
                    ->label('Device')
                    // Parsed from the stored user_agent, so old rows show it too.
                    ->getStateUsing(fn ($record): string => $record->deviceType())
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('browser')
                    ->label('Browser')
                    ->getStateUsing(fn ($record): string => $record->browser())
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Staff member')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('subject_type')
                    ->label('Resource')
                    ->options(fn (): array => \App\Models\ActivityLog::query()
                        ->whereNotNull('subject_type')->distinct()
                        ->orderBy('subject_type')->pluck('subject_type', 'subject_type')->all()),
                \Filament\Tables\Filters\Filter::make('logged_between')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
                SelectFilter::make('event')
                    // Built from data — the old hardcoded list went stale as new
                    // events were added.
                    ->options(fn (): array => \App\Models\ActivityLog::query()
                        ->distinct()->orderBy('event')->pluck('event', 'event')->all()),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
