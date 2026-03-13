<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $todaySales = Sale::query()->whereDate('sale_date', now()->toDateString());
        $lowStock = Product::query()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();

        return [
            Stat::make('Today Sales', (string) $todaySales->count())
                ->description('Sales records for today'),
            Stat::make('Today Revenue', 'NGN ' . number_format((float) $todaySales->sum('total_amount'), 2))
                ->description('Total gross sales today'),
            Stat::make('Low Stock Products', (string) $lowStock)
                ->description('Products at or below threshold'),
        ];
    }
}
