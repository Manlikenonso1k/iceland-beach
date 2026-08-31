<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Revenue overview for the dashboard.
 *
 * Two things differ from the obvious implementation, both forced by the schema:
 *
 *  - `bookings` has NO price column. Room revenue is derived from the linked
 *    room's nightly rate multiplied by the nights booked, so a booking with no
 *    room_id (legacy rows) contributes nothing rather than breaking the sum.
 *
 *  - `tickets` has no status column — it has a `paid` boolean — and `amount`
 *    is stored in KOBO, matching the payment gateways. Everything is converted
 *    to naira exactly once, at the point of display.
 *
 * The whole body is guarded: a dashboard must never 500 because one figure
 * could not be computed.
 */
class RevenueStatsWidget extends BaseWidget
{
    /** Registered explicitly on the panel, never auto-discovered. */
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    /** Commercial figures are Super Admin only. */
    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('Super Admin');
    }

    protected function getStats(): array
    {
        try {
            $now = Carbon::now();

            $roomAllTime = $this->roomRevenue();
            $roomThisMonth = $this->roomRevenue($now->copy()->startOfMonth(), $now->copy()->endOfMonth());
            $roomLastMonth = $this->roomRevenue(
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            );

            $trend = $roomLastMonth > 0
                ? round((($roomThisMonth - $roomLastMonth) / $roomLastMonth) * 100, 1)
                : null;

            $ticketAllTime = $this->ticketRevenue();
            $ticketThisMonth = $this->ticketRevenue($now->copy()->startOfMonth(), $now->copy()->endOfMonth());

            $confirmed = Booking::where('status', 'confirmed')->count();
            $pending = Booking::where('status', 'pending')->count();
            $confirmedThisMonth = Booking::where('status', 'confirmed')
                ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count();

            return [
                Stat::make('Room revenue (all time)', $this->naira($roomAllTime))
                    ->description('Confirmed bookings only')
                    ->descriptionIcon('heroicon-o-home')
                    ->color('success')
                    ->chart($this->roomRevenueByMonth()),

                Stat::make('Room revenue this month', $this->naira($roomThisMonth))
                    ->description($this->trendLabel($trend))
                    ->descriptionIcon($trend !== null && $trend < 0
                        ? 'heroicon-o-arrow-trending-down'
                        : 'heroicon-o-arrow-trending-up')
                    ->color($trend !== null && $trend < 0 ? 'warning' : 'success'),

                Stat::make('Ticket revenue this month', $this->naira($ticketThisMonth))
                    ->description('Paid tickets — ' . $now->format('F Y'))
                    ->descriptionIcon('heroicon-o-ticket')
                    ->color('info'),

                Stat::make('Ticket revenue (all time)', $this->naira($ticketAllTime))
                    ->description('All settled ticket sales')
                    ->descriptionIcon('heroicon-o-banknotes')
                    ->color('primary'),

                Stat::make('Confirmed bookings', number_format($confirmed))
                    ->description($confirmedThisMonth . ' confirmed this month')
                    ->descriptionIcon('heroicon-o-check-circle')
                    ->color('success'),

                Stat::make('Pending review', number_format($pending))
                    ->description($pending > 0 ? 'Awaiting your action' : 'Nothing waiting')
                    ->descriptionIcon('heroicon-o-clock')
                    ->color($pending > 0 ? 'warning' : 'success'),
            ];
        } catch (\Throwable $e) {
            Log::error('RevenueStatsWidget failed: ' . $e->getMessage());

            return [
                Stat::make('Revenue', '—')
                    ->description('Temporarily unavailable — see laravel.log')
                    ->color('danger'),
            ];
        }
    }

    /**
     * Confirmed room revenue in naira, optionally limited to a date window.
     *
     * Computed in PHP rather than SQL because the nights calculation differs
     * between MySQL (DATEDIFF) and SQLite (julianday), and this project runs
     * SQLite locally against MySQL in production.
     */
    private function roomRevenue(?Carbon $from = null, ?Carbon $to = null): float
    {
        $query = Booking::query()
            ->with('room')
            ->where('status', 'confirmed');

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return $query->get()->sum(function (Booking $booking): float {
            $rate = (float) ($booking->room?->room_price ?? 0);

            if ($rate <= 0) {
                return 0.0;
            }

            // A booking with no check_out still represents at least one night.
            $nights = ($booking->check_in && $booking->check_out)
                ? max(1, $booking->check_in->diffInDays($booking->check_out))
                : 1;

            return $rate * $nights;
        });
    }

    /** Paid ticket revenue in naira. tickets.amount is stored in kobo. */
    private function ticketRevenue(?Carbon $from = null, ?Carbon $to = null): float
    {
        if (! Schema::hasTable('tickets')) {
            return 0.0;
        }

        $query = Ticket::query()->where('paid', true);

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return (float) $query->sum('amount') / 100;
    }

    /**
     * Twelve-month room revenue series for the sparkline.
     *
     * Bucketed in PHP for the same cross-engine reason as roomRevenue().
     *
     * @return list<float>
     */
    private function roomRevenueByMonth(): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(11);

        $buckets = [];
        for ($i = 0; $i < 12; $i++) {
            $buckets[$start->copy()->addMonths($i)->format('Y-m')] = 0.0;
        }

        Booking::query()
            ->with('room')
            ->where('status', 'confirmed')
            ->where('created_at', '>=', $start)
            ->get()
            ->each(function (Booking $booking) use (&$buckets): void {
                $key = $booking->created_at?->format('Y-m');

                if ($key === null || ! array_key_exists($key, $buckets)) {
                    return;
                }

                $rate = (float) ($booking->room?->room_price ?? 0);
                $nights = ($booking->check_in && $booking->check_out)
                    ? max(1, $booking->check_in->diffInDays($booking->check_out))
                    : 1;

                $buckets[$key] += $rate * $nights;
            });

        return array_values($buckets);
    }

    private function naira(float $amount): string
    {
        return '₦' . number_format($amount, 2);
    }

    private function trendLabel(?float $trend): string
    {
        if ($trend === null) {
            return 'No revenue last month to compare';
        }

        return ($trend >= 0 ? '↑ ' : '↓ ') . abs($trend) . '% vs last month';
    }
}
