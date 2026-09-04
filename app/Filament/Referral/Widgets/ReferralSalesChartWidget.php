<?php

namespace App\Filament\Referral\Widgets;

use App\Models\ReferralTicketSale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Last six months of the partner's own sales. */
class ReferralSalesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Your monthly sales';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $referral = auth('referral')->user();

        if (! $referral) {
            return ['datasets' => [], 'labels' => []];
        }

        $labels = [];
        $counts = [];

        // Bucketed with an explicit range rather than whereMonth/whereYear:
        // this stays correct across a year boundary and portable across
        // SQLite and MySQL.
        foreach (range(5, 0) as $monthsAgo) {
            $month = Carbon::now()->subMonthsNoOverflow($monthsAgo);

            $labels[] = $month->format('M Y');
            $counts[] = ReferralTicketSale::query()
                ->where('referral_id', $referral->id)
                ->where('status', 'successful')
                ->whereBetween('created_at', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ])
                ->count();
        }

        return [
            'datasets' => [[
                'label' => 'Sales',
                'data' => $counts,
                'backgroundColor' => '#002F61',
                'borderColor' => '#002F61',
                'borderRadius' => 6,
            ]],
            'labels' => $labels,
        ];
    }
}
