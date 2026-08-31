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
        // Guarded for the same reason as SalesOverview. An empty collection
        // renders a zeroed chart rather than taking the dashboard down.
        try {
            $summary = Sale::query()
                ->selectRaw('payment_method, SUM(total_amount) as total')
                ->groupBy('payment_method')
                ->pluck('total', 'payment_method');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PaymentMethodBreakdown failed: ' . $e->getMessage());
            $summary = collect();
        }

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
