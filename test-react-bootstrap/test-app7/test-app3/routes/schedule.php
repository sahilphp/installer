<?php

declare(strict_types=1);

use Sahil\Scheduler\Schedule;

/**
 * Scheduled tasks for this installation.
 *
 * Loaded once by App\Providers\ScheduleProvider. Each task is a PHP callable
 * with a unique name; the name is what `schedule:list`, `schedule:run
 * --only=NAME` and the overlap lock refer to.
 *
 *   $schedule->call('prune-temp-uploads', static function (): void {
 *       // ...
 *   })
 *       ->dailyAt('03:30')
 *       ->inTimezone('Europe/Berlin')
 *       ->withoutOverlapping()
 *       ->timeoutAfter(300)
 *       ->describe('Delete temporary exports older than a day.');
 *
 * Available schedules: everyMinute(), every(N), hourly(), hourlyAt(M),
 * daily(), dailyAt('HH:MM'), weekly(), weeklyOn(0-6, 'HH:MM'), monthly(),
 * monthlyOn(1-31, 'HH:MM'), cron('* * * * *').
 */
return static function (Schedule $schedule): void {
    // Register this installation's tasks here.
};
