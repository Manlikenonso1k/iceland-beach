<?php

namespace App\Notifications;

use App\Models\OrderTransfer;
use App\Notifications\Channels\ExpoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the recipient when an order is offered to them, and to the original
 * owner when the recipient responds. `event` distinguishes the three moments.
 */
class OrderTransferNotification extends Notification
{
    use Queueable;

    public function __construct(public OrderTransfer $transfer, public string $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', ExpoChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.transfer.'.$this->event,
            'transfer_id' => $this->transfer->id,
            'order_id' => $this->transfer->order_id,
            'kind' => $this->transfer->kind,
            'status' => $this->transfer->status,
        ];
    }

    public function toExpo(object $notifiable): array
    {
        $orderId = $this->transfer->order_id;
        [$title, $body] = match ($this->event) {
            'offered' => ['Order transfer', "You've been sent order #{$orderId} — accept or decline."],
            'accepted' => ['Transfer accepted', "Your transfer of order #{$orderId} was accepted."],
            'declined' => ['Transfer declined', "Your transfer of order #{$orderId} was declined."],
            default => ['Order transfer', "Order #{$orderId}"],
        };

        return [
            'title' => $title,
            'body' => $body,
            'data' => ['order_id' => $orderId, 'transfer_id' => $this->transfer->id, 'type' => 'order.transfer'],
        ];
    }
}
