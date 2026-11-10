<?php

declare(strict_types=1);

/**
 * Task scheduler configuration.
 *
 * Tasks live in `routes/schedule.php`. `schedule:run` is meant to be invoked
 * once a minute by cron or a systemd timer (see the README): it runs every
 * task whose cron expression matches the current minute, so the external
 * trigger only has to be frequent, not exact.
 *
 * Overlap prevention:
 * - `file`      — `flock()` in `storage/framework/schedule`; correct on a
 *                 single host, and self-healing when a process crashes;
 * - `database`  — advisory locks (MySQL/PostgreSQL); required when more than
 *                 one host runs the scheduler against the same database.
 */
return [
    /* Default timezone for tasks that do not set one. */
    'timezone' => env('SCHEDULE_TIMEZONE', env('APP_TIMEZONE', 'UTC')),

    'lock' => [
        'driver' => env('SCHEDULE_LOCK', 'file'),
        'path' => env('SCHEDULE_LOCK_PATH', storage_path('framework/schedule')),
        'prefix' => env('SCHEDULE_LOCK_PREFIX', 'sahil_schedule'),
    ],
];
