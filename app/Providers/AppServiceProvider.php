<?php

namespace App\Providers;

use App\Models\Room;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            ActivityLogService::log(
                event: 'login',
                description: 'User logged in',
                subject: $event->user,
                user: $event->user,
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            ActivityLogService::log(
                event: 'logout',
                description: 'User logged out',
                subject: $user,
                user: $user,
            );
        });

        User::updated(function (User $user): void {
            if (! auth()->check()) {
                return;
            }

            $changes = collect($user->getChanges())
                ->except(['updated_at', 'remember_token', 'password'])
                ->all();

            if ($changes === []) {
                return;
            }

            ActivityLogService::log(
                event: 'user_updated',
                description: 'User profile updated',
                subject: $user,
                properties: ['changes' => $changes],
            );
        });

        User::created(function (User $user): void {
            if (! auth()->check()) {
                return;
            }

            ActivityLogService::log(
                event: 'user_created',
                description: 'User account created',
                subject: $user,
                properties: ['email' => $user->email, 'name' => $user->name],
            );
        });

        Room::updated(function (Room $room): void {
            if (! auth()->check()) {
                return;
            }

            if (! $room->wasChanged(['is_booked', 'rejection_reason'])) {
                return;
            }

            ActivityLogService::log(
                event: 'room_status_changed',
                description: 'Room booking status changed',
                subject: $room,
                properties: [
                    'room_name' => $room->room_name,
                    'previous_status' => $room->getOriginal('is_booked'),
                    'new_status' => $room->is_booked,
                    'rejection_reason' => $room->rejection_reason,
                ],
            );
        });
    }
}
