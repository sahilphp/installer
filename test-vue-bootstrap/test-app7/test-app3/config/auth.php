<?php

declare(strict_types=1);

/**
 * Authentication and authorization configuration.
 *
 * Controls user providers, password hashing, session security,
 * rate limiting, and authorization settings.
 *
 * Security best practices:
 * - Use bcrypt with cost >= 12 for password hashing
 * - Enable session fixation protection
 * - Configure appropriate rate limiting for login attempts
 * - Use secure remember me tokens
 * - Require email verification for sensitive operations
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | These options control the default authentication behavior.
    */
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'session'),
        'passwords' => env('AUTH_PASSWORDS', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Define the authentication guards for your application.
    | Guards define how users are authenticated for each request.
    */
    'guards' => [
        'session' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Define the user providers for your application.
    | Providers define how users are retrieved from storage.
    */
    'providers' => [
        'users' => [
            // SahilPHP's bundled user provider reads database rows directly;
            // there is no Eloquent/ORM provider implied by this key.
            'driver' => 'database',
            'table' => env('AUTH_USERS_TABLE', 'users'),
            'model' => env('AUTH_USER_MODEL', \App\Models\User::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Hashing Options
    |--------------------------------------------------------------------------
    |
    | Configure the password hashing algorithm and options.
    |
    | Supported algorithms:
    | - PASSWORD_BCRYPT (default, recommended)
    | - PASSWORD_ARGON2I (requires Argon2 extension)
    | - PASSWORD_ARGON2ID (requires Argon2 extension)
    */
    'password' => [
        'driver' => env('AUTH_PASSWORD_DRIVER', 'bcrypt'),
        'algorithm' => env('AUTH_PASSWORD_ALGORITHM', PASSWORD_BCRYPT),
        'cost' => (int) env('AUTH_PASSWORD_COST', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configure session-related security settings.
    */
    'session' => [
        // Regenerate session ID on login to prevent fixation
        'regenerate_on_login' => env('AUTH_REGENERATE_SESSION', true),

        // Destroy old session data on regeneration
        'destroy_on_regenerate' => env('AUTH_DESTROY_SESSION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for login attempts to prevent brute-force attacks.
    */
    'rate_limiting' => [
        // Maximum login attempts before blocking
        'max_attempts' => (int) env('AUTH_MAX_ATTEMPTS', 5),

        // Time in minutes before attempts decay
        'decay_minutes' => (int) env('AUTH_DECAY_MINUTES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember Me
    |--------------------------------------------------------------------------
    |
    | Configure "remember me" functionality.
    */
    'remember' => [
        // Enable remember me functionality
        'enabled' => env('AUTH_REMEMBER_ENABLED', true),

        // Token length in bytes (32 = 64 hex characters)
        'token_length' => (int) env('AUTH_REMEMBER_TOKEN_LENGTH', 32),

        // Remember me duration in days
        'duration_days' => (int) env('AUTH_REMEMBER_DURATION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    |
    | Configure password reset functionality.
    */
    'password_reset' => [
        // Enable password reset
        'enabled' => env('AUTH_PASSWORD_RESET_ENABLED', true),

        // Token expiration in minutes
        'token_expiration' => (int) env('AUTH_PASSWORD_RESET_EXPIRATION', 60),

        // Token length in bytes (32 = 64 hex characters)
        'token_length' => (int) env('AUTH_PASSWORD_RESET_TOKEN_LENGTH', 32),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    |
    | Configure email verification requirements.
    */
    'email_verification' => [
        // Require email verification for authentication
        'required' => env('AUTH_EMAIL_VERIFICATION_REQUIRED', false),

        // Token expiration in minutes
        'token_expiration' => (int) env('AUTH_EMAIL_VERIFICATION_EXPIRATION', 60),
    ],
];
