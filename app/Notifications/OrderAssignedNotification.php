<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** A manager assigned this order to the recipient (as server or chef). */
class OrderAssignedNotification extends Notification
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
            'type' => 'order.assigned',
            'order_id' => $this->order->id,
            'department' => $this->order->department,
            'guest_ref' => $this->order->guest_ref,
        ];
    }

    public function toExpo(object $notifiable): array
    {
        $where = $this->order->table_id ? "Table {$this->order->table_id}" : ($this->order->guest_ref ?: 'a guest');

        return [
            'title' => 'Order assigned to you',
            'body' => "A manager assigned you order #{$this->order->id} for {$where}.",
            'data' => ['order_id' => $this->order->id, 'type' => 'order.assigned'],
        ];
    }
}
