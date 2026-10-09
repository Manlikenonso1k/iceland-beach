<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Manager paged a kitchen about an overdue order. */
class OrderNudgeNotification extends Notification
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
            'type' => 'order.nudge',
            'order_id' => $this->order->id,
            'department' => $this->order->department,
        ];
    }

    public function toExpo(object $notifiable): array
    {
        $where = $this->order->table_id ? "Table {$this->order->table_id}" : ($this->order->guest_ref ?: 'a guest');

        return [
            'title' => 'Manager nudge',
            'body' => "Order #{$this->order->id} for {$where} needs attention now.",
            'data' => ['order_id' => $this->order->id, 'type' => 'order.nudge'],
        ];
    }
}
