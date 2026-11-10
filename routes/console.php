<?php

use App\Console\Commands\SendAccreditationReminders;
use App\Console\Commands\SendEntryDeadlineReminders;
use App\Console\Commands\SendRenewalReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Notices run early in the morning Harare time so a club reads them at the
 * start of its day, and on weekdays only for the ones a club has to act on.
 */
Schedule::command(SendEntryDeadlineReminders::class)
    ->dailyAt('06:30')
    ->timezone('Africa/Harare')
    ->withoutOverlapping();

Schedule::command(SendRenewalReminders::class)
    ->weeklyOn(1, '07:00')
    ->timezone('Africa/Harare')
    ->withoutOverlapping();

Schedule::command(SendAccreditationReminders::class)
    ->dailyAt('07:30')
    ->timezone('Africa/Harare')
    ->withoutOverlapping();
