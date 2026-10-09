<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Tenant;
use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * The accountability guarantee: overdue escalation fires server-side, with no
 * app open on any device. Runs every minute (schedule in routes/console.php),
 * sweeping every tenant. escalateOverdue() stamps notified_overdue_at inside a
 * transaction so a manager is paged exactly once per order.
 */
class FlagOverdueOrders extends Command
{
    protected $signature = 'orders:flag-overdue';

    protected $description = 'Notify managers of orders that have passed their due_at, exactly once each.';

    public function handle(OrderService $orders): int
    {
        // Cross-tenant sweep: no authenticated user in the scheduler, so the
        // tenant guard is bypassed deliberately here and re-applied per order.
        $due = Order::query()
            ->withoutGlobalScope('tenant')
            ->whereIn('status', Order::OPEN_STATUSES)
            ->whereNull('notified_overdue_at')
            ->where('due_at', '<', now())
            ->get();

        foreach ($due as $order) {
            Tenant::runAs((int) $order->tenant_id, fn () => $orders->escalateOverdue($order));
        }

        $this->info("Escalated {$due->count()} overdue order(s).");

        return self::SUCCESS;
    }
}
