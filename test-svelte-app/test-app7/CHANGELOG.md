# Changelog

All notable changes to `sahilphp/sahilphp` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Fixed
- The starter application no longer ships the three authentication migrations
  (`users`, `password_reset_tokens`, `sessions`); `unda:auth` owns them, so a
  new project can run `php sahil unda:migration create_users_table` without a
  name conflict.
- `php sahil unda:auth` now scaffolds the complete authentication system in one
  command: `User` model, users/reset-token/sessions migrations, four auth
  controllers, `Authenticate` / `RedirectIfAuthenticated` middleware,
  `routes/auth.php`, and the auth/dashboard views. It also adds the auth route
  require to `routes/web.php`, skips an existing users migration with a
  warning, and runs `php -l` on every generated file.

### Added
- `php sahil ui:setup` installs exactly one UI kit and one CSS framework from
  `stubs/ui-kits/` and `stubs/css/`, writes a package.json containing only the
  chosen dependencies, and deletes `stubs/` afterwards.
- `ui:setup` is wired into `post-create-project-cmd`, so
  `composer create-project` asks the two setup questions automatically.

### Changed
- Project creation asks for the UI kit and then the CSS framework before any
  file is written. The base starter-kit ships no installed UI kit or CSS
  framework code.
- The `sahilphp/firefiber`, `sahilphp/react`, `sahilphp/vue` and
  `sahilphp/svelte` packages are libraries only; `composer create-project`
  fails with a clear message.

## [1.0.0]

### Fixed
- Enforced PHP 8.4 minimum version requirement in composer.json, tooling, and code

## Unreleased - 2026-10-29

First public release.

### Added

- `php sahil key:generate` — writes a 32-byte `APP_KEY` to `.env`
  (`--force` replaces an existing key).
- `php sahil storage:prepare` — creates the writable storage tree.
- `composer create-project` hooks that copy `.env.example` to `.env`,
  generate the app key and prepare storage in one step.
- `App\Providers\EncryptionProvider` binding `Sahil\Encryption\Encrypter`
  from `APP_KEY`.
- Secure response headers from `Sahil\Http\Middleware\SecurityHeaders`,
  installed by `ObservabilityProvider` for every request.
- `queue.allowed_jobs` configuration for a queue-payload class allow-list.
- Deployment examples: `docs/deploy/nginx.conf.example`,
  `docs/deploy/apache-vhost.conf.example`, `public/.htaccess` and a static
  `public/500.html` server-level error page.
- `TESTING.md` guidance is in `README.md`; `CONTRIBUTING.md`, `SECURITY.md`
  and `CODE_OF_CONDUCT.md` are included.

### Changed

- Requires `sahilphp/framework: ^1.0` and PHP 8.4+ (was `^8.4` with a
  `path` repository and `^1.0@dev`).
- `Throttle` is constructed with an explicit store, matching the framework.
- PHPUnit moved to `^10.5`.

### Removed

- The committed `composer.lock` (a project template resolves dependencies at
  create time) and the local `path` repository used during development.

### Fixed

- `.env.example` documents `APP_KEY`, which the new commands write and the
  encrypter reads.
