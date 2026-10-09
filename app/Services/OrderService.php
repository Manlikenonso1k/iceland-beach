<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\OrderAssignedNotification;
use App\Notifications\OrderDeclinedNotification;
use App\Notifications\OrderExpeditedNotification;
use App\Notifications\OrderNudgeNotification;
use App\Notifications\OrderOverdueNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderReadyNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * The order/delay engine (HOSPI_SALES_BRIEF §1). The server owns time here:
 * due_at is stamped once at insert from a snapshotted target, and prep times are
 * frozen onto each line so a later menu edit cannot rewrite a past order's SLA.
 *
 * Copies PosService's discipline: guard, then one atomic transaction.
 */
class OrderService
{
    /**
     * Place an order. target_minutes = MAX(prep_time_minutes) across items
     * (the order is done when the slowest dish is), snapshotted at order time.
     *
     * @param  array<int, array{meal_id:int, quantity?:int, notes?:string}>  $items
     */
    public function place(
        User $waitress,
        string $department,
        array $items,
        ?int $tableId = null,
        ?string $guestRef = null,
    ): Order {
        if (! in_array($department, Meal::DEPARTMENTS, true)) {
            throw ValidationException::withMessages([
                'department' => "Unknown department: {$department}.",
            ]);
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'An order needs at least one item.',
            ]);
        }

        // Resolve meals within the caller's tenant + department only. A meal id
        // from another tenant or kitchen simply will not be found.
        $mealIds = collect($items)->pluck('meal_id')->map(fn ($id) => (int) $id)->all();
        $meals = Meal::query()
            ->where('department', $department)
            ->whereIn('id', $mealIds)
            ->get()
            ->keyBy('id');

        $lines = [];
        $target = 0;
        $total = 0;

        foreach ($items as $item) {
            $mealId = (int) ($item['meal_id'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $meal = $meals->get($mealId);

            if (! $meal) {
                throw ValidationException::withMessages([
                    'items' => "Meal {$mealId} is not on the {$department} menu.",
                ]);
            }

            if (! $meal->is_available) {
                throw ValidationException::withMessages([
                    'items' => "{$meal->name} is currently unavailable.",
                ]);
            }

            $prep = $meal->prep_time_minutes ?: Meal::DEFAULT_PREP_MINUTES;
            $target = max($target, $prep);
            $total += $meal->price * $qty;

            $lines[] = [
                'meal_id' => $meal->id,
                'name_snapshot' => $meal->name,
                'unit_price' => $meal->price,
                'quantity' => $qty,
                'prep_time_minutes_snapshot' => $prep,
                'notes' => $item['notes'] ?? null,
            ];
        }

        if ($target <= 0) {
            $target = Meal::DEFAULT_PREP_MINUTES;
        }

        // Duplicate guard (mirrors PosService's window): if the same waitress just
        // placed an identical order on this station, return it instead of making a
        // second one — stops a double-tap from creating two tickets.
        $signature = [];
        foreach ($lines as $l) {
            $signature[$l['meal_id']] = ($signature[$l['meal_id']] ?? 0) + $l['quantity'];
        }
        ksort($signature);

        $duplicate = Order::query()
            ->where('placed_by_user_id', $waitress->id)
            ->where('department', $department)
            ->where('created_at', '>=', now()->subSeconds(45))
            ->with('items')
            ->latest('id')
            ->get()
            ->first(function (Order $r) use ($signature): bool {
                $sig = [];
                foreach ($r->items as $i) {
                    $sig[$i->meal_id] = ($sig[$i->meal_id] ?? 0) + $i->quantity;
                }
                ksort($sig);

                return $sig == $signature;
            });

        if ($duplicate) {
            return $duplicate->load('items');
        }

        $order = DB::transaction(function () use ($waitress, $department, $tableId, $guestRef, $target, $total, $lines): Order {
            $placedAt = now();

            // Attribute to the chef on shift for this station (most recent open
            // shift wins). Null when no chef is on station — accept() fills it.
            $chefId = Shift::query()->open()
                ->where('department', $department)
                ->where('started_at', '<=', $placedAt)
                ->orderByDesc('started_at')
                ->orderByDesc('id')
                ->value('user_id');

            $order = Order::query()->create([
                'department' => $department,
                'placed_by_user_id' => $waitress->id,
                'chef_user_id' => $chefId,
                'table_id' => $tableId,
                'guest_ref' => $guestRef,
                'status' => 'pending',
                'placed_at' => $placedAt,
                'target_minutes' => $target,
                'due_at' => $placedAt->copy()->addMinutes($target),
                'total_amount' => $total,
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        ActivityLogService::log(
            event: 'order.placed',
            description: "Order #{$order->id} placed for {$department}",
            subject: $order,
            properties: ['target_minutes' => $target, 'total_amount' => $total],
            user: $waitress,
        );

        Notification::send(
            $this->kitchenAndManagers($order->tenant_id, $department),
            new OrderPlacedNotification($order),
        );

        return $order->load('items');
    }

    public function accept(Order $order, User $chef): Order
    {
        $this->assertChefOwnsDepartment($order, $chef);
        $this->assertStatus($order, ['pending'], 'accept');

        $updates = ['status' => 'accepted', 'accepted_at' => now()];
        // Pin the order to the accepting chef when no shift had claimed it.
        if (empty($order->chef_user_id)) {
            $updates['chef_user_id'] = $chef->id;
        }
        $order->update($updates);

        ActivityLogService::log(
            event: 'order.accepted',
            description: "Order #{$order->id} accepted",
            subject: $order,
            user: $chef,
        );

        return $order;
    }

    public function decline(Order $order, User $chef, ?string $reason): Order
    {
        $this->assertChefOwnsDepartment($order, $chef);
        $this->assertStatus($order, ['pending', 'accepted'], 'decline');

        $reason = trim((string) $reason);

        // A decline must carry a stored reason — enforced here (a DB CHECK is not
        // portable across this project's MySQL/SQLite split), covered by a test,
        // the way PosService enforces void_reason.
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required to decline an order.',
            ]);
        }

        $order->update([
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $reason,
            'declined_by_user_id' => $chef->id,
        ]);

        ActivityLogService::log(
            event: 'order.declined',
            description: "Order #{$order->id} declined: {$reason}",
            subject: $order,
            properties: ['reason' => $reason],
            user: $chef,
        );

        $recipients = $this->managers($order->tenant_id);
        if ($placedBy = $order->placedBy) {
            $recipients = $recipients->push($placedBy);
        }
        Notification::send($recipients, new OrderDeclinedNotification($order));

        return $order;
    }

    public function ready(Order $order, User $chef): Order
    {
        $this->assertChefOwnsDepartment($order, $chef);
        $this->assertStatus($order, ['accepted'], 'mark ready');

        $order->update(['status' => 'ready', 'ready_at' => now()]);

        // Tell the waitress who placed it that it's ready to pick up and serve.
        if ($placedBy = $order->placedBy) {
            Notification::send($placedBy, new OrderReadyNotification($order));
        }

        return $order;
    }

    public function complete(Order $order, User $actor): Order
    {
        $this->assertStatus($order, ['accepted', 'ready'], 'complete');

        $order->update(['status' => 'completed', 'completed_at' => now()]);

        return $order;
    }

    /**
     * Manager pages the kitchen about an order (typically overdue). Stamps
     * last_nudged_at and notifies that department's chefs. Only open orders can
     * be nudged — a completed/declined order has nothing to chase.
     */
    public function nudge(Order $order, User $manager): Order
    {
        $this->assertStatus($order, Order::OPEN_STATUSES, 'nudge');

        $order->update(['last_nudged_at' => now()]);

        ActivityLogService::log(
            event: 'order.nudged',
            description: "Order #{$order->id} nudged by manager",
            subject: $order,
            user: $manager,
        );

        $chefs = $this->withRole('Chef')
            ->where('tenant_id', $order->tenant_id)
            ->where('department', $order->department)
            ->get();

        Notification::send($chefs, new OrderNudgeNotification($order));

        return $order;
    }

    /**
     * Manager flags an in-progress order to jump the queue. Stamps expedited_at
     * and notifies the department's chefs.
     */
    public function expedite(Order $order, User $manager): Order
    {
        $this->assertStatus($order, Order::OPEN_STATUSES, 'expedite');

        $order->update(['expedited_at' => now()]);

        ActivityLogService::log(
            event: 'order.expedited',
            description: "Order #{$order->id} expedited by manager",
            subject: $order,
            user: $manager,
        );

        $chefs = $this->withRole('Chef')
            ->where('tenant_id', $order->tenant_id)
            ->where('department', $order->department)
            ->get();

        Notification::send($chefs, new OrderExpeditedNotification($order));

        return $order;
    }

    /**
     * Manager authority: directly reassign an order's waitress and/or chef (no
     * accept/decline — that peer flow is OrderTransfer). The new assignees are
     * notified. A chef must be on the order's own station.
     */
    public function reassign(Order $order, ?int $serverId, ?int $chefId, User $manager): Order
    {
        if ($serverId === null && $chefId === null) {
            throw ValidationException::withMessages(['server' => 'Pick a waitress or a chef to assign.']);
        }
        if (in_array($order->status, ['cancelled', 'declined'], true)) {
            throw ValidationException::withMessages(['order' => 'This order can no longer be reassigned.']);
        }

        $updates = [];
        $notify = collect();

        if ($serverId !== null) {
            $server = $this->withRole('Waiter')->where('tenant_id', $order->tenant_id)->find($serverId);
            if (! $server) {
                throw ValidationException::withMessages(['server_user_id' => 'Pick a valid waitress.']);
            }
            $updates['placed_by_user_id'] = $server->id;
            $notify->push($server);
        }

        if ($chefId !== null) {
            $chef = $this->withRole('Chef')
                ->where('tenant_id', $order->tenant_id)
                ->where('department', $order->department)
                ->find($chefId);
            if (! $chef) {
                throw ValidationException::withMessages(['chef_user_id' => 'Pick a chef on this order\'s station.']);
            }
            $updates['chef_user_id'] = $chef->id;
            $notify->push($chef);
        }

        $order->update($updates);

        ActivityLogService::log(
            event: 'order.reassigned',
            description: "Order #{$order->id} reassigned by manager",
            subject: $order,
            properties: $updates,
            user: $manager,
        );

        Notification::send($notify->unique('id')->values(), new OrderAssignedNotification($order));

        return $order;
    }

    /**
     * Notify managers about an order that has just tipped overdue, exactly once.
     * Stamping notified_overdue_at inside the same transaction is what guarantees
     * "exactly once" even if the sweep overlaps. Called by the scheduled command,
     * which has already set the tenant context.
     */
    public function escalateOverdue(Order $order): void
    {
        $sent = DB::transaction(function () use ($order): bool {
            $claimed = Order::query()
                ->whereKey($order->id)
                ->whereNull('notified_overdue_at')
                ->update(['notified_overdue_at' => now()]);

            return $claimed === 1;
        });

        if (! $sent) {
            return; // another sweep already paged for this order
        }

        ActivityLogService::log(
            event: 'order.overdue',
            description: "Order #{$order->id} overdue, manager notified",
            subject: $order,
        );

        Notification::send($this->managers($order->tenant_id), new OrderOverdueNotification($order));
    }

    private function assertChefOwnsDepartment(Order $order, User $chef): void
    {
        // A chef only ever touches their own kitchen's queue. Managers may be
        // allowed elsewhere, but this method is the chef path.
        if ($chef->hasRole('Chef') && $chef->department !== $order->department) {
            throw ValidationException::withMessages([
                'order' => 'This order belongs to a different kitchen.',
            ]);
        }
    }

    /** @param array<int,string> $allowed */
    private function assertStatus(Order $order, array $allowed, string $action): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot {$action} an order that is {$order->status}.",
            ]);
        }
    }

    private function kitchenAndManagers(int $tenantId, string $department): Collection
    {
        $chefs = $this->withRole('Chef')
            ->where('tenant_id', $tenantId)
            ->where('department', $department)
            ->get();

        return $chefs->merge($this->managers($tenantId))->unique('id')->values();
    }

    private function managers(int $tenantId): Collection
    {
        return $this->withRole('Manager')
            ->where('tenant_id', $tenantId)
            ->get();
    }

    /**
     * Users holding a role by NAME, tolerant of the role not existing yet — a
     * tenant with no chef must not make order placement throw (spatie's role()
     * scope would). Returns an empty set instead.
     */
    private function withRole(string $roleName): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()->whereHas('roles', fn ($q) => $q->where('name', $roleName));
    }
}
