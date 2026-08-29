<?php

namespace Tests\Feature;

use App\Filament\Widgets\BookingCalendarWidget;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The calendar previously read rooms with is_booked='booked', so a calendar of
 * unreviewed requests rendered empty — no date indicators at all. It now reads
 * bookings and shows pending alongside confirmed, distinguished by colour.
 */
class BookingCalendarWidgetTest extends TestCase
{
    use RefreshDatabase;

    private function room(string $name, string $category): Room
    {
        return Room::query()->create([
            'room_name' => $name,
            'room_category' => $category,
            'room_price' => 80000,
            'is_booked' => 'no',
        ]);
    }

    private function booking(Room $room, string $guest, string $status, int $inDays): Booking
    {
        return Booking::query()->create([
            'room_id' => $room->id,
            'guest_name' => $guest,
            'guest_email' => strtolower(str_replace(' ', '.', $guest)) . '@example.test',
            'guest_phone' => '08000000000',
            'room_type' => $room->room_name,
            'check_in' => now()->addDays($inDays),
            'check_out' => now()->addDays($inDays + 2),
            'status' => $status,
        ]);
    }

    public function test_calendar_shows_pending_and_confirmed_bookings_colour_coded_by_room(): void
    {
        $deluxe = $this->room('Deluxe Room 101', 'Deluxe');
        $suite = $this->room('Suite Room 305', 'Suite');

        $confirmed = $this->booking($deluxe, 'Ada Lovelace', 'confirmed', 1);
        $pending = $this->booking($suite, 'Grace Hopper', 'pending', 3);
        $rejected = $this->booking($suite, 'Rejected Guest', 'rejected', 5);

        $events = app(BookingCalendarWidget::class)->fetchEvents([
            'start' => now()->subMonth()->startOfMonth()->toIso8601String(),
            'end' => now()->addMonth()->endOfMonth()->toIso8601String(),
        ]);

        $eventById = collect($events)->keyBy('id');

        // Pending must appear — that was the whole bug.
        $this->assertCount(2, $events);
        $this->assertArrayHasKey($confirmed->id, $eventById);
        $this->assertArrayHasKey($pending->id, $eventById);

        // A rejected booking is not a live hold and must not occupy the calendar.
        $this->assertArrayNotHasKey($rejected->id, $eventById);

        // Confirmed stays take their room's colour; anything awaiting review
        // stays neutral grey regardless of room.
        $widget = app(BookingCalendarWidget::class);
        $this->assertSame(
            $widget->getAllRoomColors()[$deluxe->id],
            $eventById[$confirmed->id]['backgroundColor'],
        );
        $this->assertSame(
            BookingCalendarWidget::PENDING_COLOR,
            $eventById[$pending->id]['backgroundColor'],
        );

        // All-day, date-only events so a multi-night stay renders as one bar
        // rather than a single time-prefixed entry on the start day.
        $this->assertTrue($eventById[$confirmed->id]['allDay']);
        $this->assertSame($confirmed->check_in->format('Y-m-d'), $eventById[$confirmed->id]['start']);
        $this->assertSame($confirmed->check_out->format('Y-m-d'), $eventById[$confirmed->id]['end']);

        // Titles name the guest so staff can read the calendar at a glance.
        $this->assertStringContainsString('Ada Lovelace', $eventById[$confirmed->id]['title']);
        $this->assertStringContainsString('Grace Hopper', $eventById[$pending->id]['title']);

        // Room abbreviation is retained to keep month-view titles compact.
        $this->assertStringContainsString('DR-101', $eventById[$confirmed->id]['title']);
        $this->assertStringContainsString('ST-305', $eventById[$pending->id]['title']);

        $this->assertSame('block', $eventById[$confirmed->id]['display']);
    }

    public function test_booking_without_check_out_still_renders(): void
    {
        // Rows predating the check_out column have a null end date.
        $room = $this->room('Deluxe Room 101', 'Deluxe');

        $booking = Booking::query()->create([
            'room_id' => $room->id,
            'guest_name' => 'Legacy Guest',
            'guest_email' => 'legacy@example.test',
            'guest_phone' => '08000000000',
            'room_type' => $room->room_name,
            'check_in' => now()->addDays(2),
            'check_out' => null,
            'status' => 'pending',
        ]);

        $events = app(BookingCalendarWidget::class)->fetchEvents([
            'start' => now()->subMonth()->startOfMonth()->toIso8601String(),
            'end' => now()->addMonth()->endOfMonth()->toIso8601String(),
        ]);

        $eventById = collect($events)->keyBy('id');

        $this->assertArrayHasKey($booking->id, $eventById);
        $this->assertNotNull($eventById[$booking->id]['end']);
    }
}
