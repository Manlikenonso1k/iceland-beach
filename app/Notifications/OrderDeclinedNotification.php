<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderDeclinedNotification extends Notification
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
            'type' => 'order.declined',
            'order_id' => $this->order->id,
            'department' => $this->order->department,
            'reason' => $this->order->decline_reason,
        ];
    }

    public function toExpo(object $notifiable): array
    {
        return [
            'title' => 'Order declined',
            'body' => "Order #{$this->order->id} was declined: {$this->order->decline_reason}",
            'data' => ['order_id' => $this->order->id, 'type' => 'order.declined'],
        ];
    }
}
