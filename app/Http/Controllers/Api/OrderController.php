<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Meal;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    /** Waitress places an order; the server stamps due_at. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'department' => ['required', 'string'],
            'table_id' => ['nullable', 'integer'],
            'guest_ref' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.meal_id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $order = $this->orders->place(
            $request->user(),
            $data['department'],
            $data['items'],
            $data['table_id'] ?? null,
            $data['guest_ref'] ?? null,
        );

        return OrderResource::make($order->load('items', 'placedBy', 'chef'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * List orders, scoped to the caller's role: a waitress sees only her own,
     * a chef sees only their kitchen's queue, a manager sees the whole tenant.
     * Role isolation is enforced here, server-side — not by the app hiding tabs.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Order::query()->with(['items', 'placedBy', 'chef'])->latest('placed_at');

        if ($user->hasRole('Chef')) {
            $query->where('department', $user->department);
        } elseif (! $user->hasAnyRole(['Manager', 'CEO', 'Super Admin', 'Admin'])) {
            // Waitress (or any non-privileged role) sees only what she placed.
            $query->where('placed_by_user_id', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($dept = $request->query('dept')) {
            $query->where('department', $dept);
        }

        return OrderResource::collection($query->get())
            ->additional(['server_time' => now()->toIso8601String()]);
    }

    /** Manager Order Board: RED first, then AMBER, then GREEN. */
    public function board(Request $request): AnonymousResourceCollection
    {
        $query = Order::query()
            ->with(['items', 'placedBy', 'chef'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q): void {
                // Everything still open, plus completions from the last hour so
                // the board shows recent GREEN/GREY without growing unbounded.
                $q->whereNull('completed_at')
                    ->orWhere('completed_at', '>=', now()->subHour());
            })
            ->boardOrder();

        if ($dept = $request->query('dept')) {
            $query->where('department', $dept);
        }

        return OrderResource::collection($query->get())
            ->additional(['server_time' => now()->toIso8601String()]);
    }

    public function accept(Request $request, Order $order): OrderResource
    {
        return OrderResource::make(
            $this->orders->accept($order, $request->user())->load('items', 'placedBy', 'chef')
        );
    }

    public function decline(Request $request, Order $order): OrderResource
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return OrderResource::make(
            $this->orders->decline($order, $request->user(), $data['reason'] ?? null)->load('items', 'placedBy', 'chef')
        );
    }

    public function ready(Request $request, Order $order): OrderResource
    {
        return OrderResource::make(
            $this->orders->ready($order, $request->user())->load('items', 'placedBy', 'chef')
        );
    }

    public function complete(Request $request, Order $order): OrderResource
    {
        return OrderResource::make(
            $this->orders->complete($order, $request->user())->load('items', 'placedBy', 'chef')
        );
    }

    public function nudge(Request $request, Order $order): OrderResource
    {
        return OrderResource::make(
            $this->orders->nudge($order, $request->user())->load('items', 'placedBy', 'chef')
        );
    }

    public function expedite(Request $request, Order $order): OrderResource
    {
        return OrderResource::make(
            $this->orders->expedite($order, $request->user())->load('items', 'placedBy', 'chef')
        );
    }

    public function reassign(Request $request, Order $order): OrderResource
    {
        $data = $request->validate([
            'server_user_id' => ['nullable', 'integer'],
            'chef_user_id' => ['nullable', 'integer'],
        ]);

        $updated = $this->orders->reassign(
            $order,
            $data['server_user_id'] ?? null,
            $data['chef_user_id'] ?? null,
            $request->user(),
        );

        return OrderResource::make($updated->load('items', 'placedBy', 'chef'));
    }
}
