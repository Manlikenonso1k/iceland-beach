<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Support\Collection;
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

    /**
     * Wraps the package view so a room-colour legend can sit under the
     * calendar. The blade re-declares the package's Alpine mount verbatim.
     */
    protected static string $view = 'filament.widgets.booking-calendar-widget';

    protected int|string|array $columnSpan = [
        'md' => 8,
        'xl' => 9,
    ];

    /** Neutral slate for bookings that are not yet confirmed. */
    public const PENDING_COLOR = '#94a3b8';

    /**
     * Distinct hues so a month with every room booked stays readable.
     * Assigned by room position rather than a hardcoded id map, so the palette
     * keeps working if rooms are added, removed, or renumbered.
     */
    private const ROOM_PALETTE = [
        '#002F61', '#0ea5e9', '#f59e0b', '#10b981',
        '#8b5cf6', '#ef4444', '#f97316', '#06b6d4',
        '#84cc16', '#ec4899', '#6366f1', '#14b8a6',
    ];

    private ?array $roomColorCache = null;

    /**
     * Calendar events — both pending and confirmed.
     *
     * This previously queried rooms with is_booked='booked', so a calendar of
     * unreviewed requests rendered completely empty.
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
                $color = $this->getRoomColor($booking->room_id, (string) $booking->status);

                $roomLabel = $booking->room
                    ? $this->abbreviateRoomName($booking->room)
                    : ($booking->room_type ?: 'Room');

                // check_out holds the DEPARTURE day and FullCalendar's `end` is
                // exclusive, so it maps across directly — adding a day here
                // would over-run the stay by one night.
                $end = $booking->check_out ?: $booking->check_in?->copy()->addDay();

                return [
                    'id' => $booking->id,
                    'title' => trim(($booking->guest_name ?: 'Guest') . ' — ' . $roomLabel),
                    // Date-only strings make these all-day events. Full ISO
                    // datetimes made FullCalendar treat them as *timed* events,
                    // which render as a single time-prefixed entry on the start
                    // day instead of a bar spanning the stay — that was the
                    // "3-day booking only highlights the first day" bug.
                    'start' => $booking->check_in?->format('Y-m-d'),
                    'end' => $end?->format('Y-m-d'),
                    'allDay' => true,
                    'url' => BookingResource::getUrl('edit', ['record' => $booking]),
                    'shouldOpenUrlInNewTab' => false,
                    'display' => 'block',
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'status' => $booking->status,
                        'room' => $booking->room?->room_name ?: $booking->room_type,
                    ],
                ];
            })
            ->all();
    }

    /**
     * Colour for a booking bar.
     *
     * Only confirmed stays get a room colour — anything still awaiting review
     * stays neutral grey, so staff can tell a held room from a request without
     * reading the label.
     */
    public function getRoomColor(?int $roomId, string $status): string
    {
        if ($status !== 'confirmed') {
            return self::PENDING_COLOR;
        }

        return $this->getAllRoomColors()[$roomId] ?? self::ROOM_PALETTE[0];
    }

    /** @return array<int, string> room id => hex colour */
    public function getAllRoomColors(): array
    {
        if ($this->roomColorCache !== null) {
            return $this->roomColorCache;
        }

        $palette = self::ROOM_PALETTE;

        return $this->roomColorCache = Room::query()
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->mapWithKeys(fn ($id, int $index): array => [
                (int) $id => $palette[$index % count($palette)],
            ])
            ->all();
    }

    /**
     * Rooms paired with their legend colour.
     *
     * @return Collection<int, array{name: string, color: string}>
     */
    public function getLegendRooms(): Collection
    {
        $colors = $this->getAllRoomColors();

        return Room::query()
            ->orderBy('id')
            ->get(['id', 'room_name'])
            ->map(fn (Room $room): array => [
                'name' => $room->room_name ?: ('Room ' . $room->id),
                'color' => $colors[$room->id] ?? self::ROOM_PALETTE[0],
            ]);
    }

    /** Legend heading. Overridden by the space calendar, which shares the view. */
    public function getLegendLabel(): string
    {
        return 'Room legend (confirmed)';
    }

    /** Noun used in the "Pending (any …)" legend entry. */
    public function getLegendUnit(): string
    {
        return 'room';
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
            'displayEventTime' => false,
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
