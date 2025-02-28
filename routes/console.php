<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::everyMinute()
    ->withoutOverlapping()
    ->group(function () {
        Schedule::command('email:process');
        Schedule::command('trip:extend-time');
    });



