<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Tenant;
use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * The accountability guarantee: overdue escalation fires server-side, with no
 * app open on any device. Runs every minute (schedule in routes/console.php),
 * sweeping every tenant. Each overdue order is re-paged every REPEAT_MINUTES
 * (managers + chef + waiter) until it is served/cancelled.
 */
class FlagOverdueOrders extends Command
{
    protected $signature = 'orders:flag-overdue';

    protected $description = 'Page managers/chef/waiter for overdue orders, repeating every 5 minutes until resolved.';

    /** Re-page an overdue order this often until it is resolved. */
    private const REPEAT_MINUTES = 5;

    public function handle(OrderService $orders): int
    {
        // Cross-tenant sweep: no authenticated user in the scheduler, so the
        // tenant guard is bypassed deliberately here and re-applied per order.
        // (Re)page an order that is past due AND either never paged, or last
        // paged more than REPEAT_MINUTES ago — this drives the repeat loop.
        $cutoff = now()->subMinutes(self::REPEAT_MINUTES);

        $due = Order::query()
            ->withoutGlobalScope('tenant')
            ->whereIn('status', Order::OPEN_STATUSES)
            ->where('due_at', '<', now())
            ->where(fn ($q) => $q
                ->whereNull('notified_overdue_at')
                ->orWhere('notified_overdue_at', '<', $cutoff))
            ->get();

        foreach ($due as $order) {
            Tenant::runAs((int) $order->tenant_id, fn () => $orders->escalateOverdue($order));
        }

        $this->info("Escalated {$due->count()} overdue order(s).");

        return self::SUCCESS;
    }
}
