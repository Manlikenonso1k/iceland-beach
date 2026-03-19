<?php

namespace Tests\Feature;

use App\Filament\Widgets\BookingCalendarWidget;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCalendarWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_shows_only_confirmed_bookings_with_abbreviations_and_colors(): void
    {
        $deluxe = Room::query()->create([
            'room_name' => 'Deluxe Room 101',
            'room_category' => 'Deluxe',
            'room_price' => 80000,
            'is_booked' => 'booked',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(2),
        ]);

        $suite = Room::query()->create([
            'room_name' => 'Suite Room 305',
            'room_category' => 'Suite',
            'room_price' => 130000,
            'is_booked' => 'booked',
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(4),
        ]);

        $pending = Room::query()->create([
            'room_name' => 'Deluxe Room 999',
            'room_category' => 'Deluxe',
            'room_price' => 80000,
            'is_booked' => 'no',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(2),
        ]);

        $widget = app(BookingCalendarWidget::class);

        $events = $widget->fetchEvents([
            'start' => now()->subMonth()->startOfMonth()->toIso8601String(),
            'end' => now()->addMonth()->endOfMonth()->toIso8601String(),
        ]);

        $this->assertCount(2, $events);

        $eventById = collect($events)->keyBy('id');

        $this->assertArrayHasKey($deluxe->id, $eventById);
        $this->assertArrayHasKey($suite->id, $eventById);
        $this->assertArrayNotHasKey($pending->id, $eventById);

        $this->assertSame('DR-101', $eventById[$deluxe->id]['title']);
        $this->assertSame('#2563eb', $eventById[$deluxe->id]['backgroundColor']);
        $this->assertSame('block', $eventById[$deluxe->id]['display']);

        $this->assertSame('ST-305', $eventById[$suite->id]['title']);
        $this->assertSame('#d4a017', $eventById[$suite->id]['backgroundColor']);
        $this->assertSame('block', $eventById[$suite->id]['display']);
    }
}
