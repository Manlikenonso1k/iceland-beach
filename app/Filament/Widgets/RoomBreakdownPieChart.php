<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Every room as its own slice, coloured to match the booking calendar.
 *
 * Colours come from BookingCalendarWidget::getAllRoomColors() rather than a
 * second palette, so a room is the same colour here as on the calendar at
 * /admin/bookings. Change the palette there and this follows automatically.
 *
 * Confirmed bookings only, matching RoomSalesPieChart.
 */
class RoomBreakdownPieChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Sales by room';

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public ?string $filter = 'month';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasAnyRole(['Super Admin', 'Manager']);
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Today',
            'week' => 'This week',
            'month' => 'This month',
            'all' => 'All time',
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        try {
            [$from, $to] = $this->window();

            $query = Booking::query()
                ->with('room')
                ->where('status', 'confirmed')
                ->whereNotNull('room_id');

            if ($from && $to) {
                $query->whereBetween('created_at', [$from, $to]);
            }

            // Same source of truth as the calendar, so colours line up exactly.
            $roomColors = (new BookingCalendarWidget())->getAllRoomColors();

            $totals = [];
            $colors = [];

            foreach ($query->get() as $booking) {
                $label = $booking->room?->room_name ?: $booking->room_type ?: 'Unknown room';
                $totals[$label] = ($totals[$label] ?? 0) + $this->revenueFor($booking);
                $colors[$label] = $roomColors[$booking->room_id] ?? '#002F61';
            }

            arsort($totals);

            if ($totals === []) {
                return $this->emptyState();
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Revenue (NGN)',
                        'data' => array_map(fn ($v) => round($v, 2), array_values($totals)),
                        'backgroundColor' => array_map(
                            fn (string $label): string => $colors[$label],
                            array_keys($totals),
                        ),
                        'borderColor' => '#ffffff',
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => array_keys($totals),
            ];
        } catch (\Throwable $e) {
            Log::error('RoomBreakdownPieChart failed: ' . $e->getMessage());

            return $this->emptyState();
        }
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    private function window(): array
    {
        $now = Carbon::now();

        return match ($this->filter) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [null, null],
        };
    }

    private function revenueFor(Booking $booking): float
    {
        $rate = (float) ($booking->room?->room_price ?? 0);

        if ($rate <= 0) {
            return 0.0;
        }

        $nights = ($booking->check_in && $booking->check_out)
            ? max(1, $booking->check_in->diffInDays($booking->check_out))
            : 1;

        return $rate * $nights;
    }

    /** @return array<string, mixed> */
    private function emptyState(): array
    {
        return [
            'datasets' => [[
                'label' => 'Revenue (NGN)',
                'data' => [0],
                'backgroundColor' => ['#e2e8f0'],
            ]],
            'labels' => ['No confirmed sales in this period'],
        ];
    }
}
