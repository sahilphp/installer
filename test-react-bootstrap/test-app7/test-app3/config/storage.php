<?php

declare(strict_types=1);

/**
 * File storage and upload configuration.
 *
 * Disks: named storage locations, each backed by a driver. The "local"
 * driver stores files on the filesystem under a configurable root. Additional
 * drivers (remote adapters such as S3) can be registered through
 * StorageManager::extend().
 *
 * Security posture:
 * - Private disks live outside the public document root and are never
 *   served publicly (file mode 0600, directory mode 0700 by default).
 * - The public disk lives inside the public document root; files are
 *   stored under generated names with content-derived extensions, and
 *   without execute permissions. The web server must still be configured
 *   to treat public storage as static-only (no PHP execution, and
 *   `X-Content-Type-Options: nosniff`).
 * - Client-supplied MIME types, extensions, and sizes are never trusted:
 *   types are detected server-side from file content (finfo).
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | The disk used when no disk is explicitly given.
    */
    'default' => env('STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Disks
    |--------------------------------------------------------------------------
    |
    | Each disk defines a driver, a root path, and whether it is public.
    */
    'disks' => [

        /*
        |--------------------------------------------------------------------------
        | Private local disk (default)
        |--------------------------------------------------------------------------
        |
        | Private uploads and application files. Outside the public document
        | root; never served directly to clients.
        */
        'local' => [
            'driver' => 'local',
            'root' => env('STORAGE_PRIVATE_PATH', storage_path('uploads/private')),
            'public' => false,
            'permissions' => [
                'file' => 0o600,
                'directory' => 0o700,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Public local disk
        |--------------------------------------------------------------------------
        |
        | Files intended to be publicly reachable. Lives inside the public
        | document root so a web server can serve it directly. Only files
        |   explicitly stored here are exposed; private storage is never
        |   linked from or copied into this location.
        */
        'public' => [
            'driver' => 'local',
            'root' => env('STORAGE_PUBLIC_PATH', base_path('public/storage')),
            'public' => true,
            'url' => env('STORAGE_PUBLIC_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/storage'),
            'permissions' => [
                'file' => 0o644,
                'directory' => 0o755,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Defaults used by StorageManager::upload() when options are omitted.
    */
    'uploads' => [
        /* Maximum upload size in bytes (default: 10 MB). */
        'max_size' => (int) env('STORAGE_MAX_UPLOAD_SIZE', 10_485_760),

        /* MIME types permitted for uploads, detected server-side. */
        'allowed_mimes' => array_filter(
            array_map(
                'trim',
                explode(',', env('STORAGE_ALLOWED_MIMES', 'image/jpeg,image/png,image/webp,image/gif,application/pdf,text/plain'))
            )
        ),
    ],
];
