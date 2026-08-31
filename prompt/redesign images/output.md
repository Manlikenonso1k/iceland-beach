You accidentally typed `SomeBaseClass` literally in nano instead of `ChartWidget`. Let me give you the exact fix — run this single command to rewrite the file completely cleanly:

```bash
cat > app/Filament/Widgets/PaymentMethodBreakdown.php << 'EOF'
<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class PaymentMethodBreakdown extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Payment Method Breakdown';

    protected function getData(): array
    {
        $summary = Sale::query()
            ->selectRaw('payment_method, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return [
            'datasets' => [
                [
                    'label' => 'NGN',
                    'data' => [
                        (float) ($summary['cash'] ?? 0),
                        (float) ($summary['transfer'] ?? 0),
                        (float) ($summary['card'] ?? 0),
                    ],
                    'backgroundColor' => ['#2563eb', '#16a34a', '#f59e0b'],
                ],
            ],
            'labels' => ['Cash', 'Transfer', 'Card'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
EOF
```

Then fix SalesOverview too:

```bash
cat > app/Filament/Widgets/SalesOverview.php << 'EOF'
<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

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
EOF
```

Verify both are clean:

```bash
php -l app/Filament/Widgets/PaymentMethodBreakdown.php && php -l app/Filament/Widgets/SalesOverview.php
```

Both should say `No syntax errors detected`. Then:

```bash
php artisan config:cache && php artisan route:cache && php artisan event:cache && php artisan optimize:clear
```

Hard refresh the dashboard — 500 will be gone.