<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderOverdueNotification extends Notification
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
            'type' => 'order.overdue',
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
        $late = $this->order->due_at ? (int) round(abs(now()->diffInMinutes($this->order->due_at))) : 0;

        return [
            'title' => "OVERDUE: {$where} — {$late} min late",
            'body' => "Order #{$this->order->id} ({$this->order->department}) is past its {$this->order->target_minutes}-min target. Resolve it now.",
            'data' => ['order_id' => $this->order->id, 'type' => 'order.overdue'],
            // Route to the loud, high-importance Android channel + custom alarm sound.
            'channelId' => 'overdue-orders',
            'sound' => 'overdue_alarm.wav',
            'priority' => 'high',
        ];
    }
}
