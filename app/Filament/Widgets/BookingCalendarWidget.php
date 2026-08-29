<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Room;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class BookingCalendarWidget extends FullCalendarWidget
{
    /**
     * Excluded from panel auto-discovery so it renders only where it is
     * explicitly registered. Without this it was discovered panel-wide AND
     * listed in BookingCalendar::getHeaderWidgets(), rendering twice on
     * /admin/bookings.
     */
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'md' => 8,
        'xl' => 9,
    ];

    /**
     * Calendar events.
     *
     * Reads Booking, and shows BOTH pending and confirmed. It previously
     * queried rooms with is_booked='booked', so a calendar full of unreviewed
     * requests rendered completely empty — the reason no date indicators showed.
     *
     * Colour carries the status: amber = awaiting review, teal = confirmed.
     */
    public function fetchEvents(array $fetchInfo): array
    {
        return Booking::query()
            ->with('room')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotNull('check_in')
            ->where('check_in', '<', $fetchInfo['end'])
            ->where(function ($query) use ($fetchInfo): void {
                // Bookings predating the check_out column have a null end date.
                $query->where('check_out', '>', $fetchInfo['start'])
                    ->orWhereNull('check_out');
            })
            ->get()
            ->map(function (Booking $booking): array {
                $isConfirmed = $booking->status === 'confirmed';

                [$backgroundColor, $borderColor, $textColor] = $isConfirmed
                    ? ['#0f766e', '#115e59', '#ffffff']   // teal — held
                    : ['#e2924a', '#c97b33', '#1f1300'];  // amber — awaiting review

                $roomLabel = $booking->room
                    ? $this->abbreviateRoomName($booking->room)
                    : ($booking->room_type ?: 'Room');

                $end = $booking->check_out ?: optional($booking->check_in)->copy()?->addDay();

                return [
                    'id' => $booking->id,
                    'title' => trim(($booking->guest_name ?: 'Guest') . ' — ' . $roomLabel),
                    'start' => optional($booking->check_in)?->toIso8601String(),
                    'end' => $end?->toIso8601String(),
                    'url' => BookingResource::getUrl('edit', ['record' => $booking]),
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
}
