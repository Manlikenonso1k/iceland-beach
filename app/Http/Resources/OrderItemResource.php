<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meal_id' => $this->meal_id,
            'name' => $this->name_snapshot,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'prep_time_minutes' => $this->prep_time_minutes_snapshot,
            'notes' => $this->notes,
        ];
    }
}
