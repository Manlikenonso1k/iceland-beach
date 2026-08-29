<?php

namespace App\Observers;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic audit trail for records staff edit directly in Filament.
 *
 * Booking approve/reject/cancel are already logged with richer context by
 * BookingWorkflowService; this covers the gaps — room and space edits (notably
 * price changes) and referral partner creation.
 *
 * Only dirty attributes are recorded, and only ones worth auditing, so a save
 * that changed nothing meaningful does not create noise.
 */
class AuditObserver
{
    /** Attributes worth recording, per model. */
    private const WATCHED = [
        \App\Models\Room::class => ['room_name', 'room_category', 'room_price', 'is_booked', 'main_image'],
        \App\Models\ServiceBooking::class => ['service_name', 'service_category', 'service_price', 'is_booked', 'main_image'],
        \App\Models\Referral::class => ['code', 'commission_rate', 'is_active', 'user_id'],
    ];

    public function created(Model $model): void
    {
        ActivityLogService::log(
            event: $this->slug($model) . '_created',
            description: class_basename($model) . ' created',
            subject: $model,
            properties: $this->only($model, $model->getAttributes()),
        );
    }

    public function updated(Model $model): void
    {
        $changes = $this->only($model, $model->getDirty());

        if ($changes === []) {
            return;
        }

        $before = [];
        foreach (array_keys($changes) as $key) {
            $before[$key] = $model->getOriginal($key);
        }

        ActivityLogService::log(
            event: $this->slug($model) . '_updated',
            description: class_basename($model) . ' updated: ' . implode(', ', array_keys($changes)),
            subject: $model,
            properties: ['before' => $before, 'after' => $changes],
        );
    }

    public function deleted(Model $model): void
    {
        ActivityLogService::log(
            event: $this->slug($model) . '_deleted',
            description: class_basename($model) . ' deleted',
            subject: $model,
        );
    }

    /** @return array<string, mixed> */
    private function only(Model $model, array $attributes): array
    {
        $watched = self::WATCHED[$model::class] ?? [];

        return array_intersect_key($attributes, array_flip($watched));
    }

    private function slug(Model $model): string
    {
        return strtolower(class_basename($model));
    }
}
