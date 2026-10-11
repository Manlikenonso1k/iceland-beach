<?php

namespace App\Notifications\Channels;

use App\Models\DeviceToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Delivers a notification's toExpo() payload to Expo's push service for every
 * device the recipient has registered. Best-effort: a push failure must never
 * break the order flow that triggered it, so send errors are logged, not thrown.
 */
class ExpoChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toExpo')) {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $notifiable->getKey())
            ->pluck('expo_token')
            ->all();

        if ($tokens === []) {
            return;
        }

        $payload = $notification->toExpo($notifiable);

        // A notification may ask for a specific Android channel + custom sound
        // (e.g. the loud "overdue-orders" channel). Default to the general sound.
        $messages = array_map(fn (string $token): array => array_filter([
            'to' => $token,
            'title' => $payload['title'] ?? 'Hospi Sales',
            'body' => $payload['body'] ?? '',
            'data' => $payload['data'] ?? [],
            'sound' => $payload['sound'] ?? 'default',
            'priority' => $payload['priority'] ?? 'high',
            'channelId' => $payload['channelId'] ?? null, // Android routes to this channel
        ], fn ($v) => $v !== null), $tokens);

        try {
            Http::acceptJson()
                ->timeout(5)
                ->post('https://exp.host/--/api/v2/push/send', $messages);
        } catch (\Throwable $e) {
            Log::warning('Expo push failed', ['error' => $e->getMessage()]);
        }
    }
}
