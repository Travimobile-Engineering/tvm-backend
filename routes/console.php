<?php

use Illuminate\Support\Facades\Schedule;

Schedule::everyMinute()
    ->group(function () {
        Schedule::command('email:process');
        Schedule::command('trip:extend-time');
    });

Schedule::command('app:rotate-security-questions')->quarterly();
Schedule::command('app:account-payout')->everyFiveMinutes();
Schedule::command('app:withdraw-request-payout')->everyThirtySeconds();
Schedule::command('drivers:charge-usage')->dailyAt('23:00');
