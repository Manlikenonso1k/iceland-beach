<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', ExpoChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.placed',
            'order_id' => $this->order->id,
            'department' => $this->order->department,
            'due_at' => optional($this->order->due_at)->toIso8601String(),
            'table_id' => $this->order->table_id,
            'guest_ref' => $this->order->guest_ref,
        ];
    }

    public function toExpo(object $notifiable): array
    {
        $where = $this->order->table_id ? "Table {$this->order->table_id}" : ($this->order->guest_ref ?: 'a guest');

        return [
            'title' => 'New order',
            'body' => "New {$this->order->department} order for {$where}.",
            'data' => ['order_id' => $this->order->id, 'type' => 'order.placed'],
        ];
    }
}
