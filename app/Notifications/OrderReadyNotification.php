<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** The kitchen marked an order ready — tells the waitress to come and serve it. */
class OrderReadyNotification extends Notification
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
            'type' => 'order.ready',
            'order_id' => $this->order->id,
            'department' => $this->order->department,
            'table_id' => $this->order->table_id,
            'guest_ref' => $this->order->guest_ref,
        ];
    }

    public function toExpo(object $notifiable): array
    {
        $where = $this->order->table_id ? "Table {$this->order->table_id}" : ($this->order->guest_ref ?: 'a guest');

        return [
            'title' => 'Order ready',
            'body' => "Order #{$this->order->id} for {$where} is ready — pick it up.",
            'data' => ['order_id' => $this->order->id, 'type' => 'order.ready'],
        ];
    }
}
