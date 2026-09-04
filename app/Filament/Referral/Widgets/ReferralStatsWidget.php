<?php

namespace App\Filament\Referral\Widgets;

use App\Models\ReferralTicketSale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Partner's own performance. Every query is scoped to the signed-in referral.
 *
 * Money is stored in KOBO (matching the payment gateways), so every figure is
 * divided by 100 exactly once, here at the point of display.
 */
class ReferralStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $referral = auth('referral')->user();

        if (! $referral) {
            return [];
        }

        $base = fn () => ReferralTicketSale::query()
            ->where('referral_id', $referral->id)
            ->where('status', 'successful');

        $now = Carbon::now();
        $monthly = fn () => $base()->whereBetween('created_at', [
            $now->copy()->startOfMonth(),
            $now->copy()->endOfMonth(),
        ]);

        return [
            Stat::make('Your referral code', $referral->code)
                ->description('Share this with customers at checkout')
                ->descriptionIcon('heroicon-o-tag')
                ->color('primary'),

            Stat::make('Tickets sold', number_format((int) $base()->sum('quantity')))
                ->description($base()->count() . ' successful sales')
                ->descriptionIcon('heroicon-o-ticket')
                ->color('success'),

            Stat::make('This month', '₦' . number_format($monthly()->sum('commission_amount') / 100, 2))
                ->description($monthly()->count() . ' sales in ' . $now->format('F'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Total earned', '₦' . number_format($base()->sum('commission_amount') / 100, 2))
                ->description(rtrim(rtrim((string) $referral->commission_rate, '0'), '.') . '% commission rate')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning'),
        ];
    }
}
