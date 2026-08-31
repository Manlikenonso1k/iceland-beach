Here's the prompt:

You are working inside a Laravel 12 + Filament 3 project for Iceland Beach Resort.
Environment: staging.icelandbeach.com — database was cloned from production MySQL.
Problem: Transparent/overlay 500 error ONLY on /admin dashboard. All other /admin/* pages work fine.
Brand: #002F61 navy, white.

=== STEP 0: DIAGNOSE BEFORE TOUCHING ANYTHING ===

Run ALL of these. Read every single output fully.

1. Get the actual error — this is the most important step:
```
tail -n 100 storage/logs/laravel.log | grep -A 20 "ERROR\|Exception\|500"
```

2. Get the full latest error entry:
```
php artisan tinker --execute="
\$log = file_get_contents(storage_path('logs/laravel.log'));
\$entries = explode('[20', \$log);
echo '[20' . end(\$entries);
"
```

3. Check which widgets are registered on the dashboard:
```
grep -rn "getWidgets\|getDashboardWidgets\|widgets" app/Providers/Filament/AdminPanelProvider.php app/Filament/Pages/Dashboard.php 2>/dev/null
```

4. List ALL widget classes:
```
find app/Filament/Widgets -name "*.php" | sort
```
Then read EVERY widget file fully.

5. Check for a custom Dashboard page:
```
find app/Filament/Pages -name "Dashboard*" -o -name "*.php" | sort 2>/dev/null
cat app/Filament/Pages/Dashboard.php 2>/dev/null || echo "no custom dashboard page"
```

6. Check what tables exist vs what widgets query:
```
php artisan tinker --execute="
\$tables = \DB::select('SHOW TABLES');
foreach(\$tables as \$t) { \$t = (array)\$t; echo array_values(\$t)[0] . '\n'; }
"
```

7. Check for missing tables that a widget might be querying:
```
php artisan tinker --execute="
\$checks = [
    'bookings','rooms','tickets','referrals','referral_ticket_sales',
    'email_subscribers','email_campaigns','activities','notifications',
    'spaces','users'
];
foreach(\$checks as \$table) {
    echo \$table . ': ' . (\Schema::hasTable(\$table) ? 'OK' : 'MISSING') . '\n';
}
"
```

8. Check for missing columns that a widget might reference:
```
php artisan tinker --execute="
echo 'bookings: ' . implode(', ', \Schema::getColumnListing('bookings')) . '\n';
echo 'rooms: ' . implode(', ', \Schema::getColumnListing('rooms')) . '\n';
echo 'tickets: ' . implode(', ', \Schema::getColumnListing('tickets')) . '\n';
"
```

9. Check pending migrations (cloned DB may be missing new migrations):
```
php artisan migrate:status
```
Look for any 'Pending' migrations — these are the most likely cause of the 500.

10. Test each widget in isolation via tinker:
```
php artisan tinker --execute="
\$widgets = [
    \App\Filament\Widgets\BookingCalendarWidget::class,
    \App\Filament\Widgets\PendingBookingsWidget::class,
    \App\Filament\Widgets\ConfirmedBookingsWidget::class,
    \App\Filament\Widgets\SubscriberStatsWidget::class,
];
foreach(\$widgets as \$w) {
    try {
        \$instance = new \$w();
        echo \$w . ': OK\n';
    } catch (\Throwable \$e) {
        echo \$w . ': FAILED — ' . \$e->getMessage() . '\n';
    }
}
"
```

11. Check if the error is a Livewire component failing silently:
```
grep -rn "livewire\|Livewire" resources/views/filament --include="*.blade.php" 2>/dev/null | head -10
```

12. Check APP_ENV and APP_DEBUG on staging:
```
cat .env | grep -E "APP_ENV|APP_DEBUG|APP_URL|DB_DATABASE"
```

DO NOT change anything until you have read the laravel.log error and identified the root cause.
The 500 is transparent/overlay — meaning Livewire is catching it partially. 
The real error is in the log. Find it first.

=== DECISION TREE — Fix Based on Root Cause Found in Step 0 ===

--- CAUSE A: Pending migrations ---
Symptom: `migrate:status` shows Pending rows.
These migrations exist in the code but were not in the cloned production DB.

Fix:
```bash
php artisan migrate
```
If a migration fails because a column already exists:
```bash
# Find the failing migration file and wrap the column addition in a check:
if (!\Schema::hasColumn('table_name', 'column_name')) {
    $table->string('column_name');
}
```
Re-run: `php artisan migrate`
Then: `php artisan optimize:clear`
Test: hard refresh /admin — if 500 is gone, STOP here. Do not continue.

--- CAUSE B: A widget queries a table or column that doesn't exist in the cloned DB ---
Symptom: Log shows "SQLSTATE... table doesn't exist" or "column not found"

Fix: Wrap the offending widget query in a try/catch with a safe fallback:

In the failing widget class, find the getStats() or table() query method and wrap:
```php
protected function getStats(): array
{
    try {
        // existing query code
        return [
            Stat::make('...', $value),
        ];
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Widget failed: ' . $e->getMessage());
        return [
            Stat::make('Unavailable', '—')
                ->description('Data temporarily unavailable'),
        ];
    }
}
```
Apply to every widget that is failing. This prevents one broken widget from 
taking down the entire dashboard.

--- CAUSE C: A widget references a model relationship that doesn't exist ---
Symptom: Log shows "Call to undefined relationship" or "Class not found"

Fix: Find the relationship reference in the widget and guard it:
```php
// Instead of:
$booking->room->name

// Use:
optional($booking->room)->name ?? 'N/A'
// or:
$booking->room?->name ?? 'N/A'
```

--- CAUSE D: SubscriberStatsWidget or another new widget queries a table 
             that doesn't exist in the cloned production DB ---
Symptom: Log shows email_subscribers or email_campaigns table missing.

This is very likely — the cloned DB is from production which predates 
the campaign manager feature.

Fix:
```bash
php artisan migrate
```
If specific migrations fail, run them individually:
```bash
php artisan migrate --path=database/migrations/TIMESTAMP_create_email_subscribers_table.php
```

--- CAUSE E: APP_DEBUG=false hiding the error, making it look transparent ---
Symptom: .env shows APP_DEBUG=false on staging.

Temporarily enable for diagnosis ONLY:
```
APP_DEBUG=true
```
Run: `php artisan config:clear`
Reload /admin — the real error will now show fully instead of transparent overlay.
Read it, fix it, then set APP_DEBUG=false again immediately.
NEVER leave APP_DEBUG=true on a public-facing URL.

--- CAUSE F: Reverb or broadcasting config references missing .env keys ---
Symptom: Log shows "undefined array key" or "REVERB_APP_KEY" errors.

Fix: Ensure .env on staging has all Reverb keys even if Reverb isn't running:
```
BROADCAST_CONNECTION=log
REVERB_APP_ID=placeholder
REVERB_APP_KEY=placeholder
REVERB_APP_SECRET=placeholder
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```
Setting BROADCAST_CONNECTION=log disables actual broadcasting on staging 
without removing the config keys that the app expects.

=== TASK 2: Revenue Widgets on Dashboard ===

ONLY START THIS AFTER THE 500 IS RESOLVED AND DASHBOARD LOADS CLEANLY.

Add two new stats widgets to the dashboard:
1. Room Revenue — sum of bookings where status = 'confirmed', grouped sensibly
2. Ticket Revenue — sum from the tickets/orders table where payment is confirmed

STEP 1 — Understand the data:
```
php artisan tinker --execute="
// Find revenue column on bookings
echo 'booking columns: ' . implode(', ', \Schema::getColumnListing('bookings')) . '\n';

// Find tickets table structure  
\$ticketTable = \Schema::hasTable('tickets') ? 'tickets' : 
               (\Schema::hasTable('orders') ? 'orders' : 'unknown');
echo 'ticket table: ' . \$ticketTable . '\n';
if(\$ticketTable !== 'unknown') {
    echo 'ticket columns: ' . implode(', ', \Schema::getColumnListing(\$ticketTable)) . '\n';
}

// Sample confirmed booking to find price column
\$booking = \App\Models\Booking::where('status','confirmed')->first();
echo 'sample confirmed booking: ';
print_r(\$booking?->toArray());
"
```

STEP 2 — Create the Revenue widget:
`php artisan make:filament-widget RevenueStatsWidget`

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class RevenueStatsWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        try {
            // --- ROOM REVENUE ---
            // Adjust 'total_price' to whatever the actual price column is called
            // Check from Step 1: could be price, amount, total, total_amount, total_price
            $priceColumn = 'total_price'; // ADJUST THIS after reading Step 1

            $totalRoomRevenue = Booking::where('status', 'confirmed')
                ->sum($priceColumn);

            $thisMonthRoomRevenue = Booking::where('status', 'confirmed')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum($priceColumn);

            $lastMonthRoomRevenue = Booking::where('status', 'confirmed')
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->sum($priceColumn);

            // Month-over-month trend
            $roomTrend = $lastMonthRoomRevenue > 0
                ? round((($thisMonthRoomRevenue - $lastMonthRoomRevenue) / $lastMonthRoomRevenue) * 100, 1)
                : 0;

            // --- TICKET REVENUE ---
            // Adjust model and column names based on Step 1 findings
            // Common patterns: Ticket::where('payment_status','paid')->sum('amount')
            //                  Order::where('status','completed')->sum('total')
            $ticketRevenue = 0;
            $thisMonthTicketRevenue = 0;

            if (\Schema::hasTable('tickets')) {
                // Find the right status column and value for paid tickets
                $paidStatuses = ['paid', 'successful', 'completed', 'confirmed'];
                $statusColumn = \Schema::hasColumn('tickets', 'payment_status')
                    ? 'payment_status'
                    : (\Schema::hasColumn('tickets', 'status') ? 'status' : null);
                $amountColumn = \Schema::hasColumn('tickets', 'amount')
                    ? 'amount'
                    : (\Schema::hasColumn('tickets', 'total') ? 'total' : 'price');

                if ($statusColumn) {
                    $ticketRevenue = \DB::table('tickets')
                        ->whereIn($statusColumn, $paidStatuses)
                        ->sum($amountColumn);

                    $thisMonthTicketRevenue = \DB::table('tickets')
                        ->whereIn($statusColumn, $paidStatuses)
                        ->whereMonth('created_at', Carbon::now()->month)
                        ->whereYear('created_at', Carbon::now()->year)
                        ->sum($amountColumn);
                }
            }

            // --- BOOKING COUNTS ---
            $confirmedCount = Booking::where('status', 'confirmed')->count();
            $pendingCount   = Booking::where('status', 'pending')->count();
            $thisMonthCount = Booking::where('status', 'confirmed')
                ->whereMonth('created_at', Carbon::now()->month)
                ->count();

            return [
                Stat::make('Room Revenue (All Time)', '₦' . number_format($totalRoomRevenue, 2))
                    ->description('From confirmed bookings only')
                    ->descriptionIcon('heroicon-o-home')
                    ->color('success')
                    ->chart(
                        Booking::where('status', 'confirmed')
                            ->selectRaw('MONTH(created_at) as month, SUM(' . $priceColumn . ') as total')
                            ->whereYear('created_at', Carbon::now()->year)
                            ->groupBy('month')
                            ->orderBy('month')
                            ->pluck('total')
                            ->toArray()
                    ),

                Stat::make('Room Revenue This Month', '₦' . number_format($thisMonthRoomRevenue, 2))
                    ->description(
                        $roomTrend >= 0
                            ? '↑ ' . abs($roomTrend) . '% vs last month'
                            : '↓ ' . abs($roomTrend) . '% vs last month'
                    )
                    ->color($roomTrend >= 0 ? 'success' : 'warning')
                    ->descriptionIcon(
                        $roomTrend >= 0
                            ? 'heroicon-o-arrow-trending-up'
                            : 'heroicon-o-arrow-trending-down'
                    ),

                Stat::make('Ticket Revenue This Month', '₦' . number_format($thisMonthTicketRevenue, 2))
                    ->description('Paid tickets — ' . Carbon::now()->format('F Y'))
                    ->color('info')
                    ->descriptionIcon('heroicon-o-ticket'),

                Stat::make('Total Ticket Revenue', '₦' . number_format($ticketRevenue, 2))
                    ->description('All confirmed ticket sales')
                    ->color('primary')
                    ->descriptionIcon('heroicon-o-banknotes'),

                Stat::make('Confirmed Bookings', number_format($confirmedCount))
                    ->description($thisMonthCount . ' confirmed this month')
                    ->color('success')
                    ->descriptionIcon('heroicon-o-check-circle'),

                Stat::make('Pending Review', number_format($pendingCount))
                    ->description('Awaiting your action')
                    ->color($pendingCount > 0 ? 'warning' : 'success')
                    ->descriptionIcon('heroicon-o-clock'),
            ];

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RevenueStatsWidget error: ' . $e->getMessage());
            return [
                Stat::make('Revenue', '—')
                    ->description('Data unavailable — check logs')
                    ->color('danger'),
            ];
        }
    }
}
```

STEP 3 — Register RevenueStatsWidget on the dashboard.

Find where dashboard widgets are registered:
```
grep -rn "getWidgets\|widgets\(\)" app/Providers/Filament/AdminPanelProvider.php \
app/Filament/Pages/ --include="*.php"
```

Add to the dashboard widget list:
```php
\App\Filament\Widgets\RevenueStatsWidget::class,
```

Make sure it appears FIRST (sort = 1) so revenue stats are at the top of the dashboard.

STEP 4 — Scope by role. Revenue widget is for super_admin only:
```php
public static function canView(): bool
{
    return auth()->user()?->hasRole('super_admin') ?? false;
}
```

=== FINAL CHECKS ===

Phase 1 — 500 fix:
1. `php artisan migrate:status` — zero pending migrations
2. `php artisan optimize:clear && php artisan config:clear`
3. Hard refresh https://staging.icelandbeach.com/admin
4. Confirm: no 500 overlay, dashboard loads fully
5. Check storage/logs/laravel.log — no new errors on page load
6. If APP_DEBUG was temporarily set to true, set it back to false now

Phase 2 — Revenue widgets:
7. Dashboard shows RevenueStatsWidget at top with 6 stats
8. All numbers are in ₦ format
9. Room revenue only counts confirmed bookings — not pending, not rejected
10. Ticket revenue only counts paid/successful tickets
11. Month-over-month trend shows correctly (up or down arrow)
12. If logged in as non-super-admin, revenue widget is hidden

DO NOT touch:
- Payment logic or ticket PHP page
- Referral commission tracking
- Booking availability scope (pending never blocks)
- Email campaign system
- Existing role guards
- Any page that is currently working (/admin/bookings, etc.)
```

---

**Most likely cause of the 500** based on what you described — cloned production DB + staging has newer migrations = pending migrations that widgets try to query against tables that don't exist yet. The `migrate:status` check in Step 0 will confirm this in seconds. If that's it, `php artisan migrate` fixes the whole thing before you even touch the widgets.

The transparent overlay appearance is because `APP_DEBUG=false` on staging means Laravel catches the exception and Livewire renders a partial error state instead of a full crash page — you can still see through it because the Filament shell loaded but the widget Livewire components failed silently underneath.