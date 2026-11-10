<?php

declare(strict_types=1);

/**
 * Session configuration.
 *
 * Controls session storage, lifetime, and security settings.
 *
 * Security best practices:
 * - Set 'secure' to true in production (HTTPS only)
 * - Keep 'http_only' enabled to prevent JavaScript access
 * - Use 'same_site' of 'lax' or 'strict' for better CSRF protection
 * - Set appropriate 'lifetime' based on your application's security requirements
 * - Store session files outside the web root (default: storage/framework/sessions)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Session Driver
    |--------------------------------------------------------------------------
    |
    | The session driver to use. Supported drivers:
    | - 'file': File-based storage (default)
    |
    | Additional drivers can be added by implementing SessionDriverContract.
    */
    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Number of minutes the session should be valid. After this time,
    | the session will be considered expired and a new session will be created.
    |
    | Recommended values:
    | - High-security applications: 15-30 minutes
    | - Standard applications: 60-120 minutes
    | - Low-security applications: 1440 minutes (24 hours)
    */
    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    /*
    |--------------------------------------------------------------------------
    | Session Storage Path
    |--------------------------------------------------------------------------
    |
    | Path where session files are stored. This directory should be outside
    | the web root and writable by the web server process.
    |
    | Default: storage/framework/sessions
    */
    'path' => env('SESSION_PATH', storage_path('framework/sessions')),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Name of the cookie used to store the session ID. This should be unique
    | to your application to avoid conflicts with other applications on the
    | same domain.
    */
    'cookie' => env('SESSION_COOKIE', 'sahil_session'),

    /*
    |--------------------------------------------------------------------------
    | Secure Cookie
    |--------------------------------------------------------------------------
    |
    | Whether the session cookie should only be sent over HTTPS.
    |
    | IMPORTANT: Set to true in production to prevent cookie theft over
    | unencrypted connections. Set to false in local development.
    */
    'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Only Cookie
    |--------------------------------------------------------------------------
    |
    | Whether the session cookie should be inaccessible to JavaScript.
    |
    | Always keep this enabled to prevent XSS attacks from stealing session
    | cookies via document.cookie.
    */
    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookie Attribute
    |--------------------------------------------------------------------------
    |
    | SameSite attribute for the session cookie. Controls when cookies are
    | sent with cross-site requests.
    |
    | Options:
    | - 'lax': Cookies sent with top-level navigations (default, recommended)
    | - 'strict': Cookies only sent for same-site requests (most secure)
    | - 'none': Cookies sent with all requests (requires Secure, not recommended)
    |
    | For most applications, 'lax' provides the best balance of security and
    | usability. Use 'strict' for banking or other high-security applications.
    */
    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | CSRF Exclusions
    |--------------------------------------------------------------------------
    |
    | Route paths that should bypass CSRF protection. Add exclusions only when
    | necessary and document the reason in your project documentation.
    |
    | Valid use cases for exclusions:
    | - Public API endpoints with no session state
    | - Webhook endpoints from third-party services
    | - Endpoints with their own authentication mechanism
    |
    | Example: ['/api/webhook', '/stripe/callback']
    */
    'csrf_except' => array_filter(
        array_map('trim', explode(',', env('CSRF_EXCEPT', '')))
    ),
];
