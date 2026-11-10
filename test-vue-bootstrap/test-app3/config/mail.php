<?php

declare(strict_types=1);

/**
 * Mail and notification configuration.
 *
 * Transports:
 * - `smtp`  — real delivery. `security` is `tls` (STARTTLS, required),
 *             `ssl` (implicit TLS, usually port 465) or `none` (plaintext,
 *             development only). Never commit credentials; use the
 *             environment.
 * - `log`   — development only: records a summary and transmits nothing.
 * - `array` — in-memory, for tests and local inspection.
 *
 * `from` is the sender identity applied to messages that set no sender.
 * An empty address disables that fallback, and sending fails with a clear
 * error instead of inventing a sender.
 *
 * `queue.enabled` makes Notifier mail delivery (and explicit `queue()` calls)
 * asynchronous. It requires a queue worker; `send()` never uses the queue.
 * See the README for the delivery states and their meaning.
 */
return [
    'default' => env('MAIL_TRANSPORT', 'log'),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', ''),
        'name' => env('MAIL_FROM_NAME', 'SahilPHP'),
    ],

    'transports' => [
        'smtp' => [
            'driver' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => (int) env('MAIL_PORT', 587),
            'security' => env('MAIL_SECURITY', 'tls'),
            'local_domain' => env('MAIL_EHLO_DOMAIN', ''),
            'timeout' => (int) env('MAIL_TIMEOUT', 15),
            'auth' => [
                'username' => env('MAIL_USERNAME', ''),
                'password' => env('MAIL_PASSWORD', ''),
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'array' => [
            'driver' => 'array',
        ],
    ],

    'queue' => [
        'enabled' => (bool) env('MAIL_QUEUE', false),
        'name' => env('MAIL_QUEUE_NAME', 'emails'),
        'delay' => 0,
    ],
];
