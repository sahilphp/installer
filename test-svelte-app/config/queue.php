<?php

declare(strict_types=1);

/** Queue configuration. */
return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION', 'default'),
            'table' => env('QUEUE_TABLE', 'jobs'),
        ],
    ],

    'retry' => [
        'delay' => env('QUEUE_RETRY_DELAY', 0),
        'max_attempts' => env('QUEUE_MAX_ATTEMPTS', 3),
        'timeout' => env('QUEUE_TIMEOUT', 60),
    ],

    'work' => [
        'queue' => env('QUEUE_NAME', 'default'),
        'sleep' => env('QUEUE_SLEEP', 1),
    ],

    'failed' => [
        'table' => env('FAILED_JOB_TABLE', 'failed_jobs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed job classes
    |--------------------------------------------------------------------------
    |
    | Class names a queued payload is allowed to instantiate. When the list is
    | empty, payloads are unserialized without restriction (queue rows are
    | trusted application data). Set it in production to make object
    | injection impossible even if the jobs table is tampered with:
    |
    |   'allowed_jobs' => [App\Jobs\SendWelcomeEmail::class, ...],
    */
    'allowed_jobs' => [],
];
