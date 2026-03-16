<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use App\Models\Room;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class BookingCalendarWidget extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        return Room::query()
            ->where('is_booked', 'booked')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<', $fetchInfo['end'])
            ->where('end_date', '>', $fetchInfo['start'])
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'title' => $room->room_name . ($room->customer_name ? (' - ' . $room->customer_name) : ''),
                'start' => optional($room->start_date)?->toIso8601String(),
                'end' => optional($room->end_date)?->toIso8601String(),
                'url' => BookingResource::getUrl('edit', ['record' => $room]),
                'shouldOpenUrlInNewTab' => false,
                'backgroundColor' => '#f59e0b',
                'borderColor' => '#d97706',
                'textColor' => '#111827',
            ])
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
}
