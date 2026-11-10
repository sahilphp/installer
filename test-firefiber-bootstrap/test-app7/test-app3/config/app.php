<?php

declare(strict_types=1);

/** Framework core configuration. Every value here can be overridden in the environment or per installation deployment, never committed secrets belong in this file. */
return [
    // User-facing application name. Framework identity is separate so
    // renaming an application never makes it claim to be a different framework.
    'name' => env('APP_NAME', 'SahilPHP'),
    'framework' => 'SahilPHP',
    // Encryption and signing key (`base64:` + 32 random bytes). Generate one
    // with `php sahil key:generate`; never commit a real value.
    'key' => env('APP_KEY'),
    'environment' => env('APP_ENV', 'development'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'locale' => env('APP_LOCALE', 'en'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'log_level' => env('APP_LOG_LEVEL', 'info'),
    'request_id' => [
        'header' => env('REQUEST_ID_HEADER', 'X-Request-Id'),
        'idempotency_header' => env('REQUEST_ID_IDEMPOTENCY_HEADER', 'Idempotency-Key'),
    ],
    'trusted_proxies' => [
        // IP address ranges that are trusted to terminate TLS/SSL and
        // forward the real client address. Leave empty (the default) to
        // never trust forwarded headers: REMOTE_ADDR and the HTTPS /
        // REQUEST_SCHEME server variables are used.
        'proxies' => explode(',', (string) env('TRUSTED_PROXIES', '')),
        'headers' => [
            'client_ip' => ['X-Forwarded-For', 'X-Real-IP'],
            'scheme' => ['X-Forwarded-Proto'],
        ],
    ],
    'rate_limit' => [
        'enabled' => env('RATE_LIMIT_ENABLED', false),
        'store' => env('RATE_LIMIT_STORE', Sahil\Http\RateLimit\ArrayStore::class),
        'default' => [
            'limit' => (int) env('RATE_LIMIT_DEFAULT_LIMIT', 60),
            'window' => (int) env('RATE_LIMIT_DEFAULT_WINDOW', 60),
        ],
    ],
    'cors' => [
        // CORS is an HTTP header negotiation, not an authentication
        // mechanism. Keep `allow_credentials` false unless the origin
        // list is explicit.
        'enabled' => env('CORS_ENABLED', false),
        'allowed_origins' => explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => explode(',', (string) env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),
        'allowed_headers' => explode(',', (string) env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Request-Id')),
        'exposed_headers' => explode(',', (string) env('CORS_EXPOSED_HEADERS', 'X-Request-Id')),
        'max_age' => (int) env('CORS_MAX_AGE', 600),
        'allow_credentials' => env('CORS_ALLOW_CREDENTIALS', 'false') === 'true',
    ],
];
