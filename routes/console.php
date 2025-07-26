<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::everyMinute()
    ->group(function () {
        Schedule::command('email:process');
        Schedule::command('trip:extend-time');
    });

Schedule::command('app:rotate-security-questions')->monthly();

Schedule::everyTenMinutes()
    ->withoutOverlapping()
    ->group(function () {
        Schedule::command('app:account-payout');
        Schedule::command('app:withdraw-request-payout');
    });

Schedule::command('drivers:charge-usage')->dailyAt('00:00');


