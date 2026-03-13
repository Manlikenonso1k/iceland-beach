<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Waiter;
use Illuminate\Support\Carbon;

class PosService
{
    public function hasRecentDuplicate(int $tableId, int $windowSeconds = 60): bool
    {
        return Sale::query()
            ->where('table_id', $tableId)
            ->where('created_at', '>=', now()->subSeconds($windowSeconds))
            ->exists();
    }

    public function reportSummary(?Carbon $from = null, ?Carbon $to = null, ?int $waiterId = null): array
    {
        $sales = Sale::query()
            ->when($from, fn ($q) => $q->whereDate('sale_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('sale_date', '<=', $to->toDateString()))
            ->when($waiterId, fn ($q) => $q->where('waiter_id', $waiterId));

        $totalSales = (clone $sales)->count();
        $totalRevenue = (float) (clone $sales)->sum('total_amount');

        $byMethod = (clone $sales)
            ->selectRaw('payment_method, COUNT(*) as sales_count, SUM(total_amount) as amount')
            ->groupBy('payment_method')
            ->pluck('amount', 'payment_method')
            ->map(fn ($value) => (float) $value)
            ->all();

        $topItems = SaleItem::query()
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->whereHas('sale', function ($query) use ($from, $to, $waiterId) {
                $query
                    ->when($from, fn ($q) => $q->whereDate('sale_date', '>=', $from->toDateString()))
                    ->when($to, fn ($q) => $q->whereDate('sale_date', '<=', $to->toDateString()))
                    ->when($waiterId, fn ($q) => $q->where('waiter_id', $waiterId));
            })
            ->where('is_voided', false)
            ->groupBy('product_id')
            ->with('product:id,name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn (SaleItem $item) => [
                'product' => $item->product?->name,
                'quantity' => (int) $item->qty,
            ])
            ->all();

        $waiters = Waiter::query()->where('is_active', true)->pluck('full_name', 'id')->all();

        return [
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'by_method' => $byMethod,
            'top_items' => $topItems,
            'waiters' => $waiters,
        ];
    }
}
