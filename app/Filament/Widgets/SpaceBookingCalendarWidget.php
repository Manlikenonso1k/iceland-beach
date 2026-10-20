<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SpaceBookingResource;
use App\Models\ServiceBooking;
use App\Models\SpaceBooking;
use Illuminate\Support\Collection;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

/**
 * The space counterpart of BookingCalendarWidget, deliberately identical in
 * behaviour: pending bookings render neutral grey, confirmed ones take their
 * space's colour, and every bar is an all-day event so a multi-day booking
 * spans its whole range.
 *
 * Shares `filament.widgets.booking-calendar-widget` with the room calendar
 * rather than forking it — that view carries the FullCalendar package's Alpine
 * mount verbatim and must not exist twice.
 */
class SpaceBookingCalendarWidget extends FullCalendarWidget
{
    /** Registered explicitly on SpaceBookingCalendar, never auto-discovered. */
    protected static bool $isDiscovered = false;

    protected static string $view = 'filament.widgets.booking-calendar-widget';

    protected int|string|array $columnSpan = [
        'md' => 8,
        'xl' => 9,
    ];

    /** Neutral slate for bookings that are not yet confirmed. */
    public const PENDING_COLOR = '#94a3b8';

    /**
     * Same palette as the room calendar, so one visual language covers both.
     * Assigned by catalogue position, not a hardcoded id map, so it survives
     * spaces being added, removed or renumbered.
     */
    private const SPACE_PALETTE = [
        '#002F61', '#0ea5e9', '#f59e0b', '#10b981',
        '#8b5cf6', '#ef4444', '#f97316', '#06b6d4',
        '#84cc16', '#ec4899', '#6366f1', '#14b8a6',
    ];

    private ?array $spaceColorCache = null;

    public function fetchEvents(array $fetchInfo): array
    {
        return SpaceBooking::query()
            ->with('space')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotNull('check_in')
            ->where('check_in', '<', $fetchInfo['end'])
            ->where(function ($query) use ($fetchInfo): void {
                $query->where('check_out', '>', $fetchInfo['start'])
                    ->orWhereNull('check_out');
            })
            ->get()
            ->map(function (SpaceBooking $booking): array {
                $color = $this->getSpaceColor($booking->space_id, (string) $booking->status);

                $label = $booking->space?->service_name ?: ($booking->space_name ?: 'Space');

                // check_out is the departure day and FullCalendar's `end` is
                // exclusive, so it maps directly. Adding a day here would
                // over-run the booking by one.
                $end = $booking->check_out ?: $booking->check_in?->copy()->addDay();

                return [
                    'id' => $booking->id,
                    'title' => trim(($booking->guest_name ?: 'Guest') . ' — ' . $label),
                    // Date-only strings make these all-day events. Full ISO
                    // datetimes render as a single timed entry on the start day
                    // rather than a bar spanning the booking.
                    'start' => $booking->check_in?->format('Y-m-d'),
                    'end' => $end?->format('Y-m-d'),
                    'allDay' => true,
                    'url' => SpaceBookingResource::getUrl('edit', ['record' => $booking]),
                    'shouldOpenUrlInNewTab' => false,
                    'display' => 'block',
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'status' => $booking->status,
                        'space' => $label,
                    ],
                ];
            })
            ->all();
    }

    /**
     * Only confirmed bookings get a space colour — anything awaiting review
     * stays neutral grey, so staff can tell a held space from a request without
     * reading the label.
     */
    public function getSpaceColor(?int $spaceId, string $status): string
    {
        if ($status !== 'confirmed') {
            return self::PENDING_COLOR;
        }

        return $this->getAllSpaceColors()[$spaceId] ?? self::SPACE_PALETTE[0];
    }

    /** @return array<int, string> space id => hex colour */
    public function getAllSpaceColors(): array
    {
        if ($this->spaceColorCache !== null) {
            return $this->spaceColorCache;
        }

        $palette = self::SPACE_PALETTE;

        return $this->spaceColorCache = ServiceBooking::query()
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->mapWithKeys(fn ($id, int $index): array => [
                (int) $id => $palette[$index % count($palette)],
            ])
            ->all();
    }

    /**
     * Spaces paired with their legend colour. Named getLegendRooms() because
     * the shared view calls it; the "room" in the name is the view contract,
     * not the subject.
     *
     * @return Collection<int, array{name: string, color: string}>
     */
    public function getLegendRooms(): Collection
    {
        $colors = $this->getAllSpaceColors();

        return ServiceBooking::query()
            ->orderBy('id')
            ->get(['id', 'service_name'])
            ->map(fn (ServiceBooking $space): array => [
                'name' => $space->service_name ?: ('Space ' . $space->id),
                'color' => $colors[$space->id] ?? self::SPACE_PALETTE[0],
            ]);
    }

    public function getLegendLabel(): string
    {
        return 'Space legend (confirmed)';
    }

    public function getLegendUnit(): string
    {
        return 'space';
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
}
