<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CEO / Manager operations dashboard: live department revenue, overdue pressure,
 * and an accountability view of who is delaying orders (chefs on the cook,
 * waitresses on the pickup). Tenant-scoped via the authenticated user's token.
 */
class DashboardController extends Controller
{
    /** A ready order sitting longer than this (min) counts as a slow pickup. */
    private const PICKUP_THRESHOLD = 5;

    public function revenue(Request $request): JsonResponse
    {
        $period = in_array($request->query('period'), ['today', 'week', 'month'], true)
            ? $request->query('period')
            : 'today';

        $from = match ($period) {
            'week' => now()->subDays(7),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $orders = Order::query()
            ->where('placed_at', '>=', $from)
            ->whereNotIn('status', ['cancelled'])
            ->with(['placedBy:id,name', 'chef:id,name'])
            ->get();

        $now = now();

        $totalGross = 0;
        $fulfilled = 0;
        $overdueCount = 0;
        $overdueMinutes = [];
        $dept = [];
        $chefDelays = [];
        $waitDelays = [];

        foreach ($orders as $o) {
            $d = $o->department;
            $dept[$d] ??= ['revenue' => 0, 'tickets' => 0, 'overdue' => 0, 'prepSum' => 0, 'prepCount' => 0];
            $dept[$d]['tickets']++;

            $completed = $o->completed_at !== null;
            $open = in_array($o->status, Order::OPEN_STATUSES, true);
            $overdueNow = $open && $o->due_at && $now->greaterThan($o->due_at);
            $lateCompletion = $completed && $o->due_at && $o->completed_at->greaterThan($o->due_at);

            if ($o->status === 'completed') {
                $totalGross += (int) $o->total_amount;
                $fulfilled++;
                $dept[$d]['revenue'] += (int) $o->total_amount;
                if ($o->placed_at && $o->completed_at) {
                    $dept[$d]['prepSum'] += (int) round(abs($o->placed_at->diffInMinutes($o->completed_at)));
                    $dept[$d]['prepCount']++;
                }
            }

            if ($overdueNow) {
                $overdueCount++;
                $dept[$d]['overdue']++;
                $overdueMinutes[] = (int) round(abs($o->due_at->diffInMinutes($now)));
            }

            // Cook delay (chef): overdue now, or completed after its due time.
            if ($overdueNow || $lateCompletion) {
                $name = $o->chef?->name ?? 'Unassigned';
                $chefDelays[$name] = ($chefDelays[$name] ?? 0) + 1;
            }

            // Pickup delay (waitress): ready, but slow to be collected and served.
            if ($o->ready_at) {
                $pickup = $completed
                    ? (int) round(abs($o->ready_at->diffInMinutes($o->completed_at)))
                    : (int) round(abs($o->ready_at->diffInMinutes($now)));
                if ($pickup > self::PICKUP_THRESHOLD && ($completed || $o->status === 'ready')) {
                    $name = $o->placedBy?->name ?? 'Unassigned';
                    $waitDelays[$name] = ($waitDelays[$name] ?? 0) + 1;
                }
            }
        }

        $departments = collect($dept)->map(fn (array $v, string $d) => [
            'department' => $d,
            'revenue' => $v['revenue'],
            'tickets' => $v['tickets'],
            'share' => $totalGross > 0 ? round($v['revenue'] / $totalGross * 100, 1) : 0.0,
            'overdue' => $v['overdue'],
            'avg_prep_minutes' => $v['prepCount'] > 0 ? (int) round($v['prepSum'] / $v['prepCount']) : null,
        ])->sortByDesc('revenue')->values();

        $toList = fn (array $m) => collect($m)
            ->map(fn (int $count, string $name) => ['name' => $name, 'count' => $count])
            ->sortByDesc('count')->values();

        return response()->json([
            'period' => $period,
            'server_time' => $now->toIso8601String(),
            'total_gross' => $totalGross,
            'orders_fulfilled' => $fulfilled,
            'avg_ticket' => $fulfilled > 0 ? intdiv($totalGross, $fulfilled) : 0,
            'overdue_count' => $overdueCount,
            'avg_delay_minutes' => count($overdueMinutes) ? (int) round(array_sum($overdueMinutes) / count($overdueMinutes)) : 0,
            'departments' => $departments,
            'delays' => [
                'chefs' => $toList($chefDelays),
                'waitresses' => $toList($waitDelays),
            ],
        ]);
    }
}
