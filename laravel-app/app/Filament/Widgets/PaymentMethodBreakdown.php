<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class PaymentMethodBreakdown extends ChartWidget
{
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
