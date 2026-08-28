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

    public static function canCreate(): bool
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
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Actor')
                    ->searchable()
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
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'login' => 'login',
                        'logout' => 'logout',
                        'user_created' => 'user_created',
                        'user_updated' => 'user_updated',
                        'room_confirmed' => 'room_confirmed',
                        'room_rejected' => 'room_rejected',
                        'room_status_changed' => 'room_status_changed',
                    ]),
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
