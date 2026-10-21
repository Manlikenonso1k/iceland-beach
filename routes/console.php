<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hospi Sales: escalate overdue meal orders to managers, server-side, every
// minute. Run `php artisan schedule:work` locally (the dev cron) to see orders
// flip to RED and page the manager while testing.
Schedule::command('orders:flag-overdue')
    ->everyMinute()
    ->withoutOverlapping();

// Sanctum tokens now expire after 30 days (config/sanctum.php). Expiry alone
// only stops a token working — the rows stay in personal_access_tokens
// forever. This clears out anything expired for a further 7 days, so the table
// does not grow without bound on a fleet of staff phones.
Schedule::command('sanctum:prune-expired --hours=168')
    ->daily()
    ->withoutOverlapping();
