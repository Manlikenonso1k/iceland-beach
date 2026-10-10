<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\User;
use App\Notifications\OrderTransferNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Order hand-offs. A waitress sends an order she placed to another waitress; a
 * chef sends one in their station to another chef. A manager/CEO (or admin) may
 * hand off ANY order in the business — to a waitress (server kind) or to a chef
 * on that order's station (chef kind). The recipient accepts (taking ownership)
 * or declines (it stays put). Tenant scoping is inherited from the models, so
 * staff can only ever transfer within their own business.
 */
class TransferController extends Controller
{
    /** Create a pending transfer for one order. */
    public function store(Request $request, Order $order): JsonResponse
    {
        $caller = $request->user();
        $data = $request->validate(['to_user_id' => ['required', 'integer']]);

        if (in_array($order->status, ['completed', 'cancelled', 'declined'], true)) {
            throw ValidationException::withMessages(['order' => 'This order can no longer be transferred.']);
        }

        $to = User::query()->where('tenant_id', $caller->tenant_id)->find($data['to_user_id']);
        if (! $to || (int) $to->id === (int) $caller->id) {
            throw ValidationException::withMessages(['to_user_id' => 'Pick a different, valid teammate.']);
        }

        $privileged = $caller->hasAnyRole(['Manager', 'CEO', 'Super Admin', 'Admin']);

        // Decide the kind from who's asking (and, for managers, who they picked).
        if ($privileged) {
            if ($to->hasRole('Waiter')) {
                $kind = 'server';
            } elseif ($to->hasRole('Chef') && $to->department === $order->department) {
                $kind = 'chef';
            } else {
                throw ValidationException::withMessages(['to_user_id' => 'Send to a waitress, or to a chef on this order\'s station.']);
            }
        } elseif ($caller->hasRole('Waiter') && (int) $order->placed_by_user_id === (int) $caller->id) {
            $kind = 'server';
            if (! $to->hasRole('Waiter')) {
                throw ValidationException::withMessages(['to_user_id' => 'Orders can only be sent to another waitress.']);
            }
        } elseif ($caller->hasRole('Chef') && $caller->department === $order->department) {
            $kind = 'chef';
            if (! $to->hasRole('Chef') || $to->department !== $order->department) {
                throw ValidationException::withMessages(['to_user_id' => 'Orders can only be sent to a chef on the same station.']);
            }
        } else {
            abort(403, 'You can only transfer your own orders.');
        }

        if (OrderTransfer::query()->where('order_id', $order->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['order' => 'This order already has a pending transfer.']);
        }

        $transfer = OrderTransfer::query()->create([
            'order_id' => $order->id,
            'kind' => $kind,
            'from_user_id' => $caller->id,
            'to_user_id' => $to->id,
            'status' => 'pending',
        ]);

        Notification::send($to, new OrderTransferNotification($transfer, 'offered'));

        return response()->json(['data' => $this->present($transfer)], 201);
    }

    /** Teammates the caller may transfer this order to. */
    public function targets(Request $request, Order $order): JsonResponse
    {
        $caller = $request->user();
        $privileged = $caller->hasAnyRole(['Manager', 'CEO', 'Super Admin', 'Admin']);

        $query = User::query()
            ->where('tenant_id', $caller->tenant_id)
            ->where('id', '!=', $caller->id);

        if ($privileged) {
            // A manager/CEO may hand off to any waitress, or to a chef on this
            // order's station.
            $query->where(function ($w) use ($order): void {
                $w->whereHas('roles', fn ($r) => $r->where('name', 'Waiter'))
                    ->orWhere(fn ($c) => $c
                        ->whereHas('roles', fn ($r) => $r->where('name', 'Chef'))
                        ->where('department', $order->department));
            });
        } elseif ($caller->hasRole('Waiter') && (int) $order->placed_by_user_id === (int) $caller->id) {
            $query->whereHas('roles', fn ($r) => $r->where('name', 'Waiter'));
        } elseif ($caller->hasRole('Chef') && $caller->department === $order->department) {
            $query->whereHas('roles', fn ($r) => $r->where('name', 'Chef'))
                ->where('department', $order->department);
        } else {
            abort(403, 'You can only transfer your own orders.');
        }

        return response()->json(['data' => $query->orderBy('name')->get(['id', 'name'])]);
    }

    /** Transfers offered to me and still awaiting my response. */
    public function incoming(Request $request): JsonResponse
    {
        $transfers = OrderTransfer::query()
            ->where('to_user_id', $request->user()->id)
            ->where('status', 'pending')
            ->with(['order.items', 'fromUser'])
            ->latest('id')
            ->get()
            ->map(fn (OrderTransfer $t) => $this->present($t));

        return response()->json(['data' => $transfers]);
    }

    public function accept(Request $request, OrderTransfer $transfer): JsonResponse
    {
        $this->assertRecipientPending($request, $transfer);

        DB::transaction(function () use ($transfer): void {
            $order = $transfer->order;
            if ($transfer->kind === 'server') {
                $order->update(['placed_by_user_id' => $transfer->to_user_id]);
            } else {
                $order->update(['chef_user_id' => $transfer->to_user_id]);
            }
            $transfer->update(['status' => 'accepted', 'responded_at' => now()]);
        });

        Notification::send($transfer->fromUser, new OrderTransferNotification($transfer, 'accepted'));

        return response()->json(['data' => OrderResource::make($transfer->order->fresh()->load('items', 'placedBy', 'chef'))]);
    }

    public function decline(Request $request, OrderTransfer $transfer): JsonResponse
    {
        $this->assertRecipientPending($request, $transfer);

        $transfer->update(['status' => 'declined', 'responded_at' => now()]);
        Notification::send($transfer->fromUser, new OrderTransferNotification($transfer, 'declined'));

        return response()->json(['data' => $this->present($transfer)]);
    }

    private function assertRecipientPending(Request $request, OrderTransfer $transfer): void
    {
        if ((int) $transfer->to_user_id !== (int) $request->user()->id) {
            abort(403, 'This transfer was not sent to you.');
        }
        if ($transfer->status !== 'pending') {
            throw ValidationException::withMessages(['transfer' => 'This transfer was already answered.']);
        }
    }

    private function present(OrderTransfer $t): array
    {
        return [
            'id' => $t->id,
            'order_id' => $t->order_id,
            'kind' => $t->kind,
            'status' => $t->status,
            'from' => $t->fromUser?->name,
            'order' => $t->relationLoaded('order') && $t->order ? [
                'id' => $t->order->id,
                'department' => $t->order->department,
                'guest_ref' => $t->order->guest_ref,
                'table_id' => $t->order->table_id,
                'total_amount' => $t->order->total_amount,
                'items' => $t->order->relationLoaded('items')
                    ? $t->order->items->map(fn ($i) => ['name' => $i->name_snapshot, 'quantity' => $i->quantity])
                    : [],
            ] : null,
        ];
    }
}
