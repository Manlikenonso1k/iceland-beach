<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use App\Models\Room;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class BookingCalendarWidget extends FullCalendarWidget
{
    protected int|string|array $columnSpan = [
        'md' => 8,
        'xl' => 9,
    ];

    public function fetchEvents(array $fetchInfo): array
    {
        return Room::query()
            ->where('is_booked', 'booked')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<', $fetchInfo['end'])
            ->where('end_date', '>', $fetchInfo['start'])
            ->get()
            ->map(function (Room $room): array {
                [$backgroundColor, $borderColor, $textColor] = $this->roomTypeColors($room);

                return [
                    'id' => $room->id,
                    'title' => $this->abbreviateRoomName($room),
                    'start' => optional($room->start_date)?->toIso8601String(),
                    'end' => optional($room->end_date)?->toIso8601String(),
                    'url' => BookingResource::getUrl('edit', ['record' => $room]),
                    'shouldOpenUrlInNewTab' => false,
                    'display' => 'block',
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $borderColor,
                    'textColor' => $textColor,
                ];
            })
            ->all();
    }

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'height' => 'auto',
        ];
    }

    private function abbreviateRoomName(Room $room): string
    {
        $source = trim((string) ($room->room_name ?? ''));
        $category = strtolower(trim((string) ($room->room_category ?? '')));

        $prefix = match (true) {
            str_contains($category, 'deluxe') || str_contains(strtolower($source), 'deluxe') => 'DR',
            str_contains($category, 'suite') || str_contains(strtolower($source), 'suite') => 'ST',
            str_contains($category, 'executive') || str_contains(strtolower($source), 'executive') => 'ER',
            str_contains($category, 'family') || str_contains(strtolower($source), 'family') => 'FR',
            default => $this->initialsFromName($source),
        };

        preg_match('/(\d{1,4})/', $source, $matches);

        return isset($matches[1]) ? ($prefix . '-' . $matches[1]) : $prefix;
    }

    private function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'RM';
    }

    private function roomTypeColors(Room $room): array
    {
        $type = strtolower((string) ($room->room_category ?? ''));
        $name = strtolower((string) ($room->room_name ?? ''));

        return match (true) {
            str_contains($type, 'deluxe') || str_contains($name, 'deluxe') => ['#2563eb', '#1d4ed8', '#ffffff'],
            str_contains($type, 'suite') || str_contains($name, 'suite') => ['#d4a017', '#b8860b', '#111827'],
            str_contains($type, 'executive') || str_contains($name, 'executive') => ['#0f766e', '#115e59', '#ffffff'],
            str_contains($type, 'family') || str_contains($name, 'family') => ['#7c3aed', '#6d28d9', '#ffffff'],
            default => ['#334155', '#1e293b', '#ffffff'],
        };
    }
}
