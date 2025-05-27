<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// app(Schedule::class)->command('app:check-and-trigger-update')->dailyAt('00:00');
app(Schedule::class)->command('app:check-and-trigger-update')->everyMinute();
