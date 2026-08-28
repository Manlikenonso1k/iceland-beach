<?php

namespace App\Services;

use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\VoidLog;
use App\Models\Waiter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService
{
    /**
     * Execute the full POS sale transaction:
     * - table assignment guard
     * - 60-second duplicate-sale window
     * - per-item stock check (skipped for voided items)
     * - mandatory void_reason when any item is voided
     * - atomic DB transaction: sale -> sale_items -> stock decrement / void_logs -> table status
     */
    public function processSale(
        int $waiterId,
        int $tableId,
        string $paymentMethod,
        string $voidReason,
        array $items
    ): Sale {
        // 1. Table assignment guard
        $table = DiningTable::findOrFail($tableId);
        if ($table->assigned_waiter_id !== null && (int) $table->assigned_waiter_id !== $waiterId) {
            throw ValidationException::withMessages([
                'table_id' => 'This table is currently assigned to a different waiter.',
            ]);
        }

        // 2. Duplicate-sale prevention (60-second window)
        if ($this->hasRecentDuplicate($tableId)) {
            throw ValidationException::withMessages([
                'table_id' => 'A sale was just created for this table. Please wait a minute before re-submitting.',
            ]);
        }

        // 3. Build item list and validate stock
        $processedItems = [];
        $total = 0.0;
        $hasVoids = false;

        foreach ($items as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            $isVoided = (bool) ($item['is_voided'] ?? false);

            if ($pid <= 0 || $qty <= 0) {
                continue;
            }

            $product = Product::findOrFail($pid);

            if (! $isVoided && (int) $product->stock_quantity < $qty) {
                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for {$product->name} (available: {$product->stock_quantity}).",
                ]);
            }

            if (! $isVoided) {
                $total += (float) $product->price * $qty;
            } else {
                $hasVoids = true;
            }

            $processedItems[] = [
                'product_id' => $pid,
                'quantity' => $qty,
                'price' => (float) $product->price,
                'is_voided' => $isVoided,
            ];
        }

        if (empty($processedItems)) {
            throw ValidationException::withMessages([
                'items' => 'No valid items in the order.',
            ]);
        }

        // 4. Void reason required when any item is voided
        if ($hasVoids && trim($voidReason) === '') {
            throw ValidationException::withMessages([
                'void_reason' => 'A void reason is required when any item is voided.',
            ]);
        }

        // 5. Execute all writes atomically
        return DB::transaction(function () use (
            $waiterId, $tableId, $paymentMethod, $total,
            $processedItems, $voidReason, $table
        ): Sale {
            $sale = Sale::create([
                'waiter_id' => $waiterId,
                'table_id' => $tableId,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'sale_date' => now()->toDateString(),
            ]);

            foreach ($processedItems as $item) {
                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'is_voided' => $item['is_voided'],
                ]);

                if (! $item['is_voided']) {
                    Product::where('id', $item['product_id'])
                        ->decrement('stock_quantity', $item['quantity']);
                } else {
                    VoidLog::create([
                        'sale_item_id' => $saleItem->id,
                        'voided_by' => $waiterId,
                        'reason' => $voidReason,
                    ]);
                }
            }

            $table->update([
                'assigned_waiter_id' => $waiterId,
                'status' => 'billing',
            ]);

            return $sale;
        });
    }

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
