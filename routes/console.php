<?php

use Illuminate\Support\Facades\Schedule;

// Publish content whose scheduled time has arrived, expire finished alerts and
// notices, and roll the audit log. One minute-level task keeps a municipal
// site's time-sensitive postings honest without an operator watching a clock.
Schedule::command('municipal:publish-due')->everyMinute()->withoutOverlapping();

// Nightly housekeeping: prune audit rows past the retention window.
Schedule::command('municipal:housekeeping')->dailyAt('03:30')->withoutOverlapping();

// Arrest blotter retention: unpublish records past their retention window. A
// no-op while the Jail And Arrest Records module is disabled.
Schedule::command('records:expire')->dailyAt('03:15')->withoutOverlapping();

// Self-update: apply a newer signed release soon after it is published, unless
// the operator turned auto-update off.
Schedule::command('app:update')
    ->everyFiveMinutes()
    ->when(fn () => \App\Services\UpdateService::autoEnabled())
    ->withoutOverlapping();

// Admin "Update Now" requests, serviced within a minute by the scheduler so the
// command runs as the right user with the right PHP binary.
Schedule::command('app:update')
    ->everyMinute()
    ->when(fn () => \App\Models\Setting::get('update_requested') === '1')
    ->withoutOverlapping();
