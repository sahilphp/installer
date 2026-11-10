<?php

declare(strict_types=1);

/**
 * Runtime asset configuration.
 *
 * The UI commands (`ui:install`, `ui:dev`, `ui:build`) produce the source and
 * build files this configuration points at. Templates should use the
 * `@asset_css`, `@asset_js` and `asset()` helpers instead of hard-coding
 * URLs, so development and production resolve to the right place.
 *
 * - `profile` is normally `auto`: `.sahil-ui.json` (written by `ui:install`)
 *   is authoritative, with `package.json` as a fallback. Set it explicitly
 *   only when an application needs to override the recorded profile.
 * - `mode` is normally `auto`: `production` when `app.environment` is
 *   `production`, development otherwise. `development` points tags at the
 *   Vite dev server; `production` points at `public/build` and verifies the
 *   expected files exist.
 * - `dev_server` is the Vite URL used in development. `ui:dev` records the
 *   server it actually started in `public/hot`, and that file wins over this
 *   value, so a busy port does not require a configuration change.
 * - `base_url` prefixes public URLs for applications deployed below a path
 *   (for example `/app`) or served from a CDN. Leave it empty for root-relative
 *   URLs.
 * - `build_path` is the filesystem directory Vite writes; `build_public_path`
 *   is the URL path that directory is served under.
 * - `verify` makes production asset resolution fail with an actionable error
 *   when a build output is missing. Keep it enabled outside of unusual
 *   deployments where assets are served from another host.
 */

return [
    'profile' => env('ASSET_PROFILE', 'auto'),
    'mode' => env('ASSET_MODE', 'auto'),
    'dev_server' => env('ASSET_DEV_SERVER', 'http://127.0.0.1:5173'),
    'base_url' => env('ASSET_URL', ''),
    'build_path' => 'public/build',
    'build_public_path' => 'build',
    'verify' => env('ASSET_VERIFY', true),
];
