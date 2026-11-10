<?php

declare(strict_types=1);

/**
 * View (template) configuration.
 *
 * - `paths` are searched IN ORDER and the first hit wins, so an application
 *   can override a package's template by putting its own directory first.
 *   Relative paths are resolved against the application base path; absolute
 *   paths are used as given.
 * - `cache` is where compiled templates live. It holds only generated files:
 *   deleting its contents is always safe (the next render regenerates them).
 * - `extension` must start with a dot. `.sahil.php` keeps templates out of
 *   the way of plain `.php` files — and is compiled, never executed
 *   directly, so it must not be reachable over HTTP.
 */

return [
    'paths' => [
        'resources/views',
    ],

    'cache' => 'storage/framework/views',

    'extension' => '.sahil.php',
];
