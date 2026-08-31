You are working inside a Laravel 12 + Filament 3 + ReactJS project for Iceland Beach Resort.
Brand: #002F61 navy, white.

=== STEP 0: MAP EVERYTHING BEFORE TOUCHING ANYTHING ===

1. `cat composer.json | grep -i "reverb\|pusher\|soketi\|broadcasting\|websocket\|echo\|livewire"`
2. `cat package.json | grep -i "echo\|pusher\|reverb\|socket\|broadcast"`
3. `cat config/broadcasting.php`
4. `cat .env | grep -i "broadcast\|pusher\|reverb\|websocket\|queue"`
5. `find app/Events -type f -name "*.php" 2>/dev/null | sort`
6. `find app/Listeners -type f -name "*.php" 2>/dev/null | sort`
7. `find app/Filament/Widgets -type f -name "*.php" | sort`
8. `find app/Filament/Resources/BookingResource -type f -name "*.php" | sort`
9. `grep -rn "dispatch\|event\|notify\|notification" app/Http/Controllers --include="*.php" | grep -i book | head -20`
10. `grep -rn "getHeaderWidgets\|getFooterWidgets\|getWidgets" app/Filament --include="*.php"`
11. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('bookings'));"`
12. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('notifications')) ?? 'no table';"`
13. `cat config/queue.php | grep -i "default\|driver"`
14. `php artisan reverb:start --help 2>/dev/null && echo "reverb available" || echo "reverb not installed"`

Read every result fully before writing a single line of code.
Understand: what broadcasting driver is configured, what queue driver is set, what events already exist.

=== DECISION POINT — Choose Broadcasting Approach ===

After reading Step 0, determine which approach to use:

IF Laravel Reverb is already installed (reverb in composer.json):
→ Use Reverb. Skip Reverb installation step, just configure.

IF Pusher credentials exist in .env:
→ Use Pusher driver. Skip Reverb installation.

IF neither — this is the default for a fresh Laravel project:
→ Install Laravel Reverb (free, self-hosted, no third-party account needed).
→ Run: `php artisan install:broadcasting`
   This installs Reverb + sets up Echo automatically. Answer yes to all prompts.
→ Then: `composer require laravel/reverb`
→ Then: `npm install --save-dev laravel-echo pusher-js`

Document which path was taken before proceeding.

=== TASK 1: Booking Created Event ===

STEP 1 — Create the event:
`php artisan make:event BookingCreated`

```php
<?php
namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        return [new Channel('bookings')]; // public channel — admin is already authenticated
    }

    public function broadcastAs(): string
    {
        return 'booking.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->booking->id,
            'guest_name' => $this->booking->guest_name // adjust to actual column
                            ?? $this->booking->name
                            ?? 'A guest',
            'room'       => $this->booking->room->name ?? 'a room',
            'check_in'   => \Carbon\Carbon::parse($this->booking->check_in)->format('M j, Y'),
            'check_out'  => \Carbon\Carbon::parse($this->booking->check_out)->format('M j, Y'),
            'status'     => $this->booking->status,
        ];
    }
}
```

STEP 2 — Create BookingStatusChanged event (fires on approve/reject/cancel):
`php artisan make:event BookingStatusChanged`

```php
<?php
namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $oldStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('bookings')];
    }

    public function broadcastAs(): string
    {
        return 'booking.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->booking->id,
            'guest_name' => $this->booking->guest_name ?? $this->booking->name ?? 'Guest',
            'room'       => $this->booking->room->name ?? 'Room',
            'old_status' => $this->oldStatus,
            'new_status' => $this->booking->status,
        ];
    }
}
```

=== TASK 2: Dispatch Events at the Right Points ===

STEP 1 — Find exactly where bookings are created. Check:
grep -rn "Booking::create|booking->save|BookingController|store(" app/Http/Controllers app/Filament --include="*.php" | head -20

In the BookingController store() method (or wherever new bookings are saved), add AFTER the booking is saved:

```php
// After: $booking = Booking::create([...]);
$booking->load('room'); // ensure room relationship is loaded
\App\Events\BookingCreated::dispatch($booking);
```

STEP 2 — Find exactly where status is changed (confirm/reject/cancel actions in Filament widgets).
Check the PendingBookingsWidget and ConfirmedBookingsWidget action closures.

In EACH action closure where status is updated, capture old status first:

```php
->action(function (Booking $record) {
    $oldStatus = $record->status; // capture BEFORE update
    $record->update(['status' => 'confirmed']); // or rejected / cancelled
    $record->load('room');
    \App\Events\BookingStatusChanged::dispatch($record, $oldStatus);
    
    // existing notification code stays here unchanged
    \Filament\Notifications\Notification::make()
        ->title('Booking confirmed')
        ->success()
        ->send();
}),
```

Do the same for reject and cancel actions. Do NOT remove any existing logic — only add the event dispatch after the update.

STEP 3 — If a BookingObserver exists, check it:
find app/Observers -name "Booking*" 2>/dev/null
If a BookingObserver handles status changes, dispatch the events there instead to avoid double-firing.
If no observer exists, the controller/widget dispatch from above is correct.

=== TASK 3: Database Notifications for Filament (the WhatsApp-style bell) ===

Filament 3 has a built-in database notification bell. Wire it up properly.

STEP 1 — Ensure notifications table exists:
php artisan tinker --execute="\Schema::hasTable('notifications') ? print('exists') : print('run migration');"`
If missing: `php artisan notifications:table && php artisan migrate`

STEP 2 — Send a database notification to ALL admin users when a booking is created.

Create the notification class:
`php artisan make:notification NewBookingNotification`

```php
<?php
namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewBookingNotification extends Notification
{
    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Filament reads from database channel
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => '🏖️ New Booking Received',
            'body'    => ($this->booking->guest_name ?? $this->booking->name ?? 'A guest')
                         . ' booked '
                         . ($this->booking->room->name ?? 'a room')
                         . ' — '
                         . \Carbon\Carbon::parse($this->booking->check_in)->format('M j')
                         . ' to '
                         . \Carbon\Carbon::parse($this->booking->check_out)->format('M j, Y'),
            'icon'    => 'heroicon-o-calendar-days',
            'color'   => 'info',
            'actions' => [
                [
                    'label' => 'View Booking',
                    'url'   => '/admin/bookings/' . $this->booking->id . '/edit',
                ],
            ],
        ];
    }
}
```

STEP 3 — Create BookingStatusNotification:
`php artisan make:notification BookingStatusNotification`

```php
<?php
namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

class BookingStatusNotification extends Notification
{
    public function __construct(
        public Booking $booking,
        public string $oldStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $status = $this->booking->status;
        $icon   = match($status) {
            'confirmed'  => 'heroicon-o-check-circle',
            'rejected'   => 'heroicon-o-x-circle',
            'cancelled'  => 'heroicon-o-trash',
            default      => 'heroicon-o-arrow-path',
        };
        $color = match($status) {
            'confirmed' => 'success',
            'rejected'  => 'danger',
            'cancelled' => 'warning',
            default     => 'info',
        };
        $guestName = $this->booking->guest_name ?? $this->booking->name ?? 'Guest';

        return [
            'title' => ucfirst($status) . ': ' . $guestName,
            'body'  => "Booking #{$this->booking->id} for "
                       . ($this->booking->room->name ?? 'a room')
                       . " changed from {$this->oldStatus} → {$status}",
            'icon'  => $icon,
            'color' => $color,
        ];
    }
}
```

STEP 4 — Create a listener that sends these database notifications to all admin users:
`php artisan make:listener SendBookingNotificationToAdmins --event=BookingCreated`

```php
<?php
namespace App\Listeners;

use App\Events\BookingCreated;
use App\Models\User;
use App\Notifications\NewBookingNotification;

class SendBookingNotificationToAdmins
{
    public function handle(BookingCreated $event): void
    {
        // Notify all users with admin roles — adjust role names to match yours
        $admins = User::role(['super_admin', 'admin', 'staff'])
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new NewBookingNotification($event->booking));
        }
    }
}
```

`php artisan make:listener SendStatusNotificationToAdmins --event=BookingStatusChanged`

```php
<?php
namespace App\Listeners;

use App\Events\BookingStatusChanged;
use App\Models\User;
use App\Notifications\BookingStatusNotification;

class SendStatusNotificationToAdmins
{
    public function handle(BookingStatusChanged $event): void
    {
        $admins = User::role(['super_admin', 'admin', 'staff'])->get();

        foreach ($admins as $admin) {
            $admin->notify(new BookingStatusNotification(
                $event->booking,
                $event->oldStatus,
            ));
        }
    }
}
```

STEP 5 — Register events + listeners in app/Providers/EventServiceProvider.php:
```php
protected $listen = [
    \App\Events\BookingCreated::class => [
        \App\Listeners\SendBookingNotificationToAdmins::class,
    ],
    \App\Events\BookingStatusChanged::class => [
        \App\Listeners\SendStatusNotificationToAdmins::class,
    ],
];
```

STEP 6 — Enable the Filament notification bell in AdminPanelProvider.php:
```php
->databaseNotifications()
->databaseNotificationsPolling('10s') // polls every 10 seconds for new notifications
```

This gives you the WhatsApp-style bell icon in the Filament top bar that shows unread count and notification list.

=== TASK 4: Auto-Refresh Widgets After Booking Actions ===

PROBLEM: After confirming/rejecting/cancelling a booking, the widgets don't refresh — you have to manually reload.

APPROACH: Use Filament's built-in widget polling + Livewire event dispatch. No custom JS needed. No risk of breaking existing logic.

STEP 1 — Add polling to all three booking widgets (PendingBookingsWidget, ConfirmedBookingsWidget, BookingCalendarWidget).

In each widget class, add:
```php
protected static ?string $pollingInterval = '10s'; // refreshes every 10 seconds automatically
```

This alone solves the stale data problem for most cases.

STEP 2 — For instant refresh after an action (so you don't wait 10 seconds), dispatch a Livewire event after each action closure:

In PendingBookingsWidget, after each action:
```php
->action(function (Booking $record) {
    $oldStatus = $record->status;
    $record->update(['status' => 'confirmed']);
    $record->load('room');
    \App\Events\BookingStatusChanged::dispatch($record, $oldStatus);

    \Filament\Notifications\Notification::make()
        ->title('Booking confirmed')
        ->success()
        ->send();

    // Dispatch Livewire event to refresh sibling widgets
    $this->dispatch('booking-status-updated');
}),
```

STEP 3 — Make each widget listen for that event and refresh:

In PendingBookingsWidget:
```php
protected function getListeners(): array
{
    return [
        'booking-status-updated' => '$refresh',
    ];
}
```

In ConfirmedBookingsWidget:
```php
protected function getListeners(): array
{
    return [
        'booking-status-updated' => '$refresh',
    ];
}
```

In BookingCalendarWidget (if it extends a Livewire component):
```php
protected function getListeners(): array
{
    return [
        'booking-status-updated' => '$refresh',
    ];
}
```

This means: when you click Confirm on a pending booking, that widget refreshes instantly AND dispatches an event that causes the other two widgets to refresh simultaneously. No page reload needed.

STEP 4 — Also handle the reverse: when a new booking arrives from the front-end (ReactJS booking form), the admin panel should update without manual refresh.

If using Reverb/broadcasting, add to the calendar widget:

```php
// At the top of the widget class
use Livewire\Attributes\On;

#[On('echo:bookings,booking.created')]
public function refreshOnNewBooking(): void
{
    // This fires when a new booking comes in via websocket
    // $refresh is handled by the attribute
}
```

For the pending widget similarly:
```php
#[On('echo:bookings,booking.created')]
public function refreshOnNewBooking(): void {}
```

STEP 5 — Bootstrap Echo in the Filament layout if not already done.

Check resources/js/app.js or resources/js/echo.js for Echo initialization.
If using Reverb, it should look like:
```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

If this already exists from `php artisan install:broadcasting`, leave it untouched.
Run: `npm run build`

=== TASK 5: Verify Reverb is Running ===

Add this to your server startup (or run manually during development):
```bash
php artisan reverb:start
```

For production, create a supervisor config:
```ini
[program:reverb]
command=php /path-to-project/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path-to-project/storage/logs/reverb.log
```

Check .env has these set (php artisan install:broadcasting sets them automatically):
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

=== FINAL CHECKS ===
1. `php artisan optimize:clear && php artisan config:clear`
2. `npm run build`
3. `php artisan migrate` (notifications table)
4. `php artisan queue:work` in one terminal
5. `php artisan reverb:start` in another terminal
6. Open /admin in browser — confirm bell icon appears in top bar
7. Submit a test booking from the front-end rooms page
8. Confirm: bell shows unread count immediately (within 10s polling at most)
9. Click the bell — confirm notification shows guest name, room, dates
10. Click Confirm on a pending booking — confirm pending widget refreshes
    instantly AND confirmed widget updates without page reload
11. Click Reject — confirm same instant refresh behaviour
12. DO NOT touch: payment logic, referral system, ticket PHP page,
    email campaign system, existing role guards, availability scope