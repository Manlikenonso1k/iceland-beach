<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 *
 * Every order payload carries the server's clock (server_time), the SLA
 * deadline (due_at), the derived status_colour and seconds_remaining. The app
 * seeds its countdown from server_time and renders status_colour as-is; it never
 * recomputes the traffic-light rule, so client and server cannot drift.
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = now();

        return [
            'id' => $this->id,
            'department' => $this->department,
            'status' => $this->status,
            'status_colour' => $this->statusColour($now),
            'table_id' => $this->table_id,
            'guest_ref' => $this->guest_ref,
            'placed_by' => $this->whenLoaded('placedBy', fn () => $this->placedBy?->name),
            'placed_by_phone' => $this->whenLoaded('placedBy', fn () => $this->placedBy?->phone),
            'chef' => $this->whenLoaded('chef', fn () => $this->chef?->name),
            'chef_phone' => $this->whenLoaded('chef', fn () => $this->chef?->phone),
            'placed_at' => optional($this->placed_at)->toIso8601String(),
            'target_minutes' => $this->target_minutes,
            'due_at' => optional($this->due_at)->toIso8601String(),
            'accepted_at' => optional($this->accepted_at)->toIso8601String(),
            'ready_at' => optional($this->ready_at)->toIso8601String(),
            'completed_at' => optional($this->completed_at)->toIso8601String(),
            'declined_at' => optional($this->declined_at)->toIso8601String(),
            'decline_reason' => $this->decline_reason,
            'last_nudged_at' => optional($this->last_nudged_at)->toIso8601String(),
            'expedited_at' => optional($this->expedited_at)->toIso8601String(),
            'total_amount' => $this->total_amount,
            'seconds_remaining' => $this->secondsRemaining($now),
            'server_time' => $now->toIso8601String(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
