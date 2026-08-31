<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Room sales by category, as a pie.
 *
 * "Accepted sales only" — the query is restricted to confirmed bookings, so
 * pending requests and rejections never appear in revenue.
 *
 * `bookings` carries no price column, so revenue is derived the same way as
 * RevenueStatsWidget: the linked room's nightly rate times the nights booked.
 * Both are computed in PHP rather than SQL because DATEDIFF/MONTH do not exist
 * in SQLite, and this project develops on SQLite against MySQL in production.
 */
class RoomSalesPieChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Room sales by category';

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
                ->where('status', 'confirmed');

            if ($from && $to) {
                $query->whereBetween('created_at', [$from, $to]);
            }

            $byCategory = [];

            foreach ($query->get() as $booking) {
                $category = $booking->room?->room_category ?: 'Uncategorised';
                $byCategory[$category] = ($byCategory[$category] ?? 0) + $this->revenueFor($booking);
            }

            arsort($byCategory);

            if ($byCategory === []) {
                return $this->emptyState();
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Revenue (NGN)',
                        'data' => array_map(fn ($v) => round($v, 2), array_values($byCategory)),
                        'backgroundColor' => $this->categoryColors(array_keys($byCategory)),
                        'borderColor' => '#ffffff',
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => array_keys($byCategory),
            ];
        } catch (\Throwable $e) {
            Log::error('RoomSalesPieChart failed: ' . $e->getMessage());

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

    /**
     * Category colours drawn from the same palette the booking calendar uses,
     * so a category reads consistently across the admin.
     *
     * @param  list<string>  $categories
     * @return list<string>
     */
    private function categoryColors(array $categories): array
    {
        $palette = ['#002F61', '#0ea5e9', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444'];

        return array_map(
            fn (int $i): string => $palette[$i % count($palette)],
            array_keys(array_values($categories)),
        );
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
