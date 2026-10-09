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
