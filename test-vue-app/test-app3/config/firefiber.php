<?php

declare(strict_types=1);

/**
 * Firefiber configuration.
 *
 * The master HMAC key is normally derived from APP_KEY by the provider. Set
 * FIREFIBER_HMAC_KEY only when you deliberately need an independent
 * Firefiber secret.
 */
return [
    'action_path' => env('FIREFIBER_ACTION_PATH', '/firefiber'),
    'upload_path' => env('FIREFIBER_UPLOAD_PATH', '/firefiber/uploads'),
    'hmac_key' => env('FIREFIBER_HMAC_KEY'),
    'snapshot_expiration_seconds' => (int) env('FIREFIBER_SNAPSHOT_EXPIRATION', 300),
    'state_ttl_seconds' => (int) env('FIREFIBER_STATE_TTL', 0),
    'assets' => [
        'public_prefix' => env('FIREFIBER_ASSET_PREFIX', '/firefiber/assets'),
        'development' => env('FIREFIBER_ASSETS_DEVELOPMENT'),
    ],
];
