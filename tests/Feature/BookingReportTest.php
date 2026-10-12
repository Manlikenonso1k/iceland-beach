<?php

namespace Tests\Feature;

use App\Filament\Resources\BookingResource\Pages\BookingCalendar;
use App\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingReportService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingReportTest extends TestCase
{
    use RefreshDatabase;

    private function room(string $name = 'Gold Suite 7', float $price = 95000): Room
    {
        return Room::query()->create([
            'room_name' => $name,
            'room_category' => 'Gold',
            'room_price' => $price,
            'is_booked' => 'no',
        ]);
    }

    private function booking(Room $room, string $guest, string $checkIn, ?string $checkOut, string $status = 'confirmed'): Booking
    {
        return Booking::query()->create([
            'room_id' => $room->id,
            'guest_name' => $guest,
            'guest_phone' => '08030000000',
            'room_type' => $room->room_name,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
        ]);
    }

    private function actAsStaff(): void
    {
        $this->actingAs(User::query()->create([
            'name' => 'Front Desk',
            'email' => 'victorynonso9@gmail.com',
            'password' => 'password',
        ]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_daily_range_covers_the_whole_day(): void
    {
        [$start, $end, $label] = app(BookingReportService::class)->range(['period' => 'daily', 'date' => '2026-10-12']);

        $this->assertSame('2026-10-12 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-12 23:59:59', $end->format('Y-m-d H:i:s'));
        $this->assertStringStartsWith('Daily', $label);
    }

    public function test_weekly_range_is_monday_to_sunday_of_the_picked_date(): void
    {
        // 2026-10-15 is a Thursday.
        [$start, $end] = app(BookingReportService::class)->range(['period' => 'weekly', 'date' => '2026-10-15']);

        $this->assertSame('2026-10-12 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-18 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_monthly_range_and_label(): void
    {
        [$start, $end, $label] = app(BookingReportService::class)->range(['period' => 'monthly', 'month' => 2, 'year' => 2026]);

        $this->assertSame('2026-02-01', $start->toDateString());
        $this->assertSame('2026-02-28 23:59:59', $end->format('Y-m-d H:i:s'));
        $this->assertSame('Monthly — February 2026', $label);
    }

    public function test_rows_only_include_the_period_and_skip_rejected_bookings(): void
    {
        $gold = $this->room();
        $silver = $this->room('Silver Room 2', 40000);

        $this->booking($gold, 'Ada Obi', '2026-10-03 14:00', '2026-10-06 12:00');      // 3 nights = 285,000
        $this->booking($silver, 'Tunde Bello', '2026-10-20 14:00', null, 'pending');   // 1 night = 40,000
        $this->booking($gold, 'Rejected Guest', '2026-10-10 14:00', '2026-10-11 12:00', 'rejected');
        $this->booking($gold, 'November Guest', '2026-11-01 14:00', '2026-11-02 12:00');

        $service = app(BookingReportService::class);
        [$start, $end] = $service->range(['period' => 'monthly', 'month' => 10, 'year' => 2026]);
        $rows = $service->rows($start, $end);

        $this->assertSame(['Ada Obi', 'Tunde Bello'], $rows->pluck('occupant')->all());
        $this->assertSame(['Gold Suite 7', 'Silver Room 2'], $rows->pluck('room')->all());
        $this->assertEquals(325000.0, $rows->sum('amount'));
    }

    public function test_list_page_action_downloads_the_pdf(): void
    {
        $this->actAsStaff();
        $this->booking($this->room(), 'Ada Obi', '2026-10-03 14:00', '2026-10-06 12:00');

        Livewire::test(ListBookings::class)
            ->assertActionVisible('downloadReport')
            ->callAction('downloadReport', data: ['period' => 'monthly', 'month' => 10, 'year' => 2026])
            ->assertHasNoActionErrors()
            ->assertFileDownloaded('iceland-report-monthly-2026-10.pdf');
    }

    public function test_calendar_page_action_downloads_a_weekly_pdf(): void
    {
        $this->actAsStaff();

        Livewire::test(BookingCalendar::class)
            ->assertActionVisible('downloadReport')
            ->callAction('downloadReport', data: ['period' => 'weekly', 'date' => '2026-10-15'])
            ->assertHasNoActionErrors()
            ->assertFileDownloaded('iceland-report-weekly-2026-10-12.pdf');
    }

    public function test_the_rendered_pdf_is_a_real_pdf(): void
    {
        $this->booking($this->room(), 'Ada Obi', '2026-10-12 14:00', '2026-10-13 12:00');

        $response = app(BookingReportService::class)->download(['period' => 'daily', 'date' => '2026-10-12']);

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertStringContainsString('iceland-report-daily-2026-10-12.pdf', (string) $response->headers->get('content-disposition'));
    }
}
