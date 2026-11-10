# SahilPHP Starter Kit

A ready-to-run application for the SahilPHP framework: routing, views,
sessions, authentication, database, queue, mail, and a Tailwind CSS frontend
(or Bootstrap 5, selected when generating a project) — all wired and testable
from the first `composer install`.

The framework itself is the reusable `sahilphp/framework` package. This
repository owns the application: configuration, providers, routes, views,
entry points and tests.

The canonical project directory is `sahilphp/`, and the canonical Composer
package is `sahilphp/sahilphp`. There is no separate `starter-kit/` project
directory in this monorepo.

## Requirements



## Server requirements

PHP 8.4+ is required to run this application. Most shared hosts offer
PHP 8.4 or newer. If your host is running an older PHP version, you will need
to upgrade or switch to a different hosting provider.

Required extensions:
- ext-mbstring
- ext-json
- ext-openssl
- ext-pdo and ext-pdo_sqlite (default database)
- ext-ctype
- ext-tokenizer
- ext-fileinfo

Optional extensions:
- ext-gd (image manipulation)
- ext-xmlreader
- ext-xmlwriter

Optional: A writable `storage/` directory (it ships with `.gitkeep` files)
Optional: MySQL or PostgreSQL for database-backed features

The application boots with sensible defaults when no `.env` file is present:
- `APP_ENV=development`
- `APP_DEBUG=false`
- `APP_TIMEZONE=UTC`

For production deployment, set `APP_ENV=production` and `APP_DEBUG=false`.

See the [PHP documentation](https://www.php.net/) for installation instructions.

- **PHP 8.4+** (this kit's `composer.json` requires `^8.4`)
- **Composer 2.x**
- **Node.js 20.19+ or 22.12+ with npm** — only for the optional frontend
  asset commands (`npm run dev`, `php sahil ui:build`). The PHP application
  boots without Node.
- A writable `storage/` directory (it ships with `.gitkeep` files)
- Optional: MySQL or PostgreSQL when you outgrow the built-in SQLite default

## Installation

```bash
composer create-project sahilphp/sahilphp my-app
cd my-app
```

`create-project` copies the application, installs `sahilphp/framework` and its
dependencies, then runs four hooks:

1. `.env.example` is copied to `.env` (skipped when `.env` already exists);
2. `php sahil key:generate` writes a unique `APP_KEY`;
3. `php sahil storage:prepare` creates the writable storage tree;
4. `php sahil ui:setup` asks for one UI kit and one CSS framework and installs
   only those stubs.

No `.env` file is strictly required: the application boots with the
documented defaults (`APP_ENV=development`, `APP_DEBUG=false`, UTC). Copy the
template yourself when you need database, mail, session or asset overrides:

```bash
cp .env.example .env
php sahil key:generate      # writes APP_KEY; --force replaces an existing key
```

### Creating a project with the installer

The starter-kit also contains a guided installer, so a checkout can create
another application without publishing anything first:

```bash
php sahil new my-app
```

The installer asks for the project name and then for exactly one UI framework:

```text
Choose your UI framework:
  [1] Tailwind CSS
  [2] Bootstrap 5
```

This checkout-local `php sahil new` path is the legacy two-profile installer
(Tailwind or Bootstrap). For the five UI choices — Firefiber, React, Vue,
Svelte or none — use the global `sahilphp new --ui-kit=... --css=...` installer.

It copies the application sources, removes frontend files that do not belong to
the selected profile, installs PHP and frontend dependencies, and generates
`.env` with a fresh `APP_KEY`. `vendor/`, `node_modules/`, build output,
sessions and machine-local files are never copied from the source checkout.

Non-interactive use is supported for automation and CI. A UI choice is
mandatory in that mode; the installer never falls back to installing both:

```bash
php sahil new my-app --ui=tailwind --no-interaction
php sahil new my-app --path=/tmp/my-app --ui=bootstrap --no-interaction
php sahil new my-app --ui=tailwind --no-install --no-interaction
```

| Option | Meaning |
|---|---|
| `name` | Project name (letters, digits, dots, underscores and hyphens) |
| `--path=DIR` | Exact destination directory (default: `./<name>`) |
| `--ui=NAME` | `tailwind` or `bootstrap`; required when non-interactive |
| `--profile=NAME` | Alias for `--ui` |
| `--no-interaction`, `-n` | Never prompt; requires a name and `--ui` |
| `--interactive` | Force prompts even when STDIN is not a TTY |
| `--no-install` | Generate files without running Composer or npm |
| `--no-dev` | Run Composer without development dependencies |

The installer refuses non-empty destination directories and never overwrites an
existing project. After creation, the printed next steps show how to review the
application, build the selected assets and start the server.

### Working on the framework locally

To develop against an unreleased framework, clone it next to this repository
and point Composer at that checkout (remove the entry before committing):

```bash
composer config repositories.framework \
  '{"type":"path","url":"../framework","options":{"symlink":true,"versions":{"sahilphp/framework":"1.1.0"}}}'
composer update sahilphp/framework
```

## First run

Verify the application boots (no database required):

```bash
php sahil about
php sahil --version
php sahil env:check        # lists known variables; never prints values
php sahil key:generate     # only needed when .env has no APP_KEY yet
php sahil storage:prepare  # create any missing storage directories
```

Start the development server from the project root:

```bash
php sahil start                    # http://127.0.0.1:8000
php sahil start --port=8088        # a different port
php sahil start --host=0.0.0.0 --port=8088
composer start -- --port=8088      # the same command through Composer
```

`php sahil <command>` runs every CLI command without the `bin/` prefix;
`php sahil start` is the identical command. When the port is already taken,
`start` moves to the next free port and prints both, so `composer start` never
fails with a raw "Address already in use" error.

`start` runs PHP's built-in development server with `-t public` as the document
root, so static assets under `public/` are served alongside the application.

Then check it:

```bash
curl -s http://127.0.0.1:8000/health
# {"status":"ok","framework":"SahilPHP","environment":"development","booted":true,...}

curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8000/no-such-page
# 404
```

Open <http://127.0.0.1:8000/> for the sample home page and
<http://127.0.0.1:8000/ui-kit> for the component gallery.

Press `Ctrl+C` to stop the server.

## Frontend assets

A new project always asks **two questions, in order, before any file is
written**:

1. **Which UI kit** — Firefiber, React, Vue, Svelte, or None.
2. **Which CSS framework** — Tailwind CSS v4 or Bootstrap 5.

The answers are stored in `.sahil-ui.json` and control which stubs are
installed. A project always gets exactly one UI kit and one CSS framework,
never two. `sahilphp new` asks both questions (or accepts `--ui-kit` and
`--css`); `composer create-project` runs `php sahil ui:setup`, which asks the
same questions unless the installer already supplied
`SAHILPHP_UI_KIT` / `SAHILPHP_CSS`.

### How stubs work

`stubs/` is a source tree, not installed code. It contains one directory per
UI kit under `stubs/ui-kits/` (`firefiber`, `react`, `vue`, `svelte`, `none`)
and one per CSS framework under `stubs/css/` (`tailwind`, `bootstrap`).

```bash
php sahil ui:setup --ui-kit=react --css=bootstrap
```

`ui:setup` copies only the chosen trees, writes a `package.json` and
`vite.config.js` containing only the chosen dependencies, then **deletes the
whole `stubs/` directory**. No unchosen kit survives in the project. Interactive
runs ask both questions; non-interactive runs default to `none` + `tailwind`.

Asset URLs are generated by the active profile, so the same templates work in
development and production.

**Development** — start Vite next to the PHP server (two terminals):

```bash
npm ci
npm run dev                    # or: php sahil ui:dev
```

In development the layouts read `public/hot`, the file `ui:dev` writes with the
Vite server it actually started. Vite starts on `http://127.0.0.1:5173` and
moves to the next free port when that one is busy — the pages follow, no
restart or config edit needed. Without Vite running, the pages still render but
are unstyled.

**Production** — build deterministic files into `public/build/assets/`:

```bash
npm ci
php sahil ui:build --clean
APP_ENV=production php sahil start
```

Build output is gitignored, so **a fresh checkout must run the build before
serving with `APP_ENV=production`**; otherwise `/` and `/ui-kit` return a safe
`500` because the expected asset files are missing.

Check the frontend state at any time:

```bash
php sahil ui:status        # add --json for machine-readable output
```

`ui:install` remains available for re-configuring an existing checkout
(`tailwind`, `bootstrap`, `both`, `none`); add `--dry-run` to preview, `--merge`
or `--overwrite` to resolve existing files.

## Branding

The official SahilPHP logo ships in every project and every layout already
links it:

| File | Purpose |
|---|---|
| `public/favicon.ico` | browser tab icon |
| `public/logo.png` | official lockup (mark + wordmark) for navigation and heroes |
| `public/logo-mark.png` | mark only, for footers and compact surfaces |
| `public/apple-touch-icon.png` | iOS home-screen icon |

Templates render the logo through `partials/brand`, so replacing those files
updates the welcome page, navigation, auth pages and dashboard at once.
`APP_NAME` changes the application name in titles and copy; the logo remains
the framework mark.

## Authentication scaffolding

Generate a complete server-side authentication implementation in one command:

```bash
php sahil unda:auth                 # complete auth implementation in one command
php sahil unda:auth --ui=bootstrap  # choose a page style explicitly
php sahil unda:auth --dry-run       # print the plan, write nothing
php sahil unda:auth --force         # replace existing files (backup kept)
```

Tailwind projects get shadcn/ui-style pages and a sidebar dashboard; Bootstrap
projects get a Bootstrap 5 design. The command writes an ORM-backed
`App\Models\User`, the users, password-reset-token and sessions migrations,
four controllers (`Login`, `Register`, `ForgotPassword`, `ResetPassword`),
`Authenticate` / `RedirectIfAuthenticated` middleware, `routes/auth.php`, and
the templates under `resources/views/`. It also adds
`require __DIR__.'/auth.php';` to `routes/web.php` when missing. The handlers use the framework's
`AuthManager`, `PasswordResetManager`, database provider, sessions, and CSRF
middleware; passwords use the configured hash settings. The dashboard requires
an account, and authenticated users are redirected away from guest-only routes.

| Route | Template |
|---|---|
| `/login` | `auth/login.sahil.php` |
| `/register` | `auth/register.sahil.php` |
| `/forgot-password` | `auth/forgot-password.sahil.php` |
| `/reset-password/{token}` | `auth/reset-password.sahil.php` |
| `/dashboard` | `pages/dashboard.sahil.php` |

See `docs/unda-auth.md` for the complete file list, `--force` semantics, and
how to extend the generated implementation.

`WebRoutesProvider` loads `routes/auth.php` automatically when it exists, so
the routes work as soon as the command finishes. Each form has a matching POST
route: submissions are validated server-side and invalid input re-renders the
same page with the field errors and the values the user typed (HTTP 200),
never a 405 or a bare error page. Sign-in, registration and password reset go
through `AuthManager` / `PasswordResetManager`; success redirects (or shows a
status message for resets), and `POST /logout` ends the session.

Run the migrations once before using the forms:

```bash
php sahil migrate
```

Password-reset mail uses the configured transport. The default
`MAIL_TRANSPORT=log` records only that a send was attempted — it never writes
subject, body or recipients — so configure `MAIL_TRANSPORT=smtp` (or point it
at a local mail catcher such as Mailpit) to receive the reset link. Set a real
`MAIL_FROM_ADDRESS` as well; the template ships a placeholder. Existing pages
are never overwritten without `--force`, which backs them up under
`storage/framework/auth-backups/`.

## CLI

`php sahil` boots the application from `bootstrap/app.php` for commands that
need runtime services. UI and `unda:` generation commands run before boot, so
they work in a partially configured project.

```bash
php sahil --help
php sahil about                         # app name, environment, debug, PHP, booted
php sahil config:show database          # secrets are always [redacted]
php sahil env:check                     # variable names only, never values
php sahil ui:setup [--ui-kit=...] [--css=...]
php sahil ui:install|ui:dev|ui:build|ui:status|ui:theme
php sahil unda:auth [--ui=tailwind|bootstrap] [--force] [--dry-run]
php sahil migrate|migrate:status|migrate:rollback|db:seed
php sahil queue:work|queue:failed|queue:retry|queue:flush
php sahil schedule:list|schedule:run
```

### Code generation

The generators use the `unda:` namespace and write through the framework's
shared generation service, so paths and namespaces come from this
application's own `composer.json` PSR-4 map:

```bash
php sahil unda:controller UserController
php sahil unda:controller Admin/UserController
php sahil unda:model User
php sahil unda:model Billing/Invoice --migration
php sahil unda:migration create_posts_table
php sahil unda:migration add_email_to_users_table
php sahil unda:middleware Authenticate
php sahil unda:policy UserPolicy
php sahil unda:provider PaymentServiceProvider
php sahil unda:command SendInvoice
php sahil unda:job ProcessPayment
php sahil unda:seeder UserSeeder
```

Every generator validates its name, refuses to overwrite an existing file
unless `--force` is passed (originals are backed up under
`storage/framework/unda-backups/`), prints the path relative to the
application root, and syntax-checks the result. Run
`php sahil unda:<type> --help` for the options of one generator. The old
`make:` generator names are not registered; only the documented `make:auth`
compatibility alias remains.

### Firefiber

When the Firefiber UI kit is selected, `ui:setup` registers the package
provider and adds the package commands to `bootstrap/commands.php`:

```bash
php sahil firefiber:page Demo --example
php sahil firefiber:component Counter
```

The page is discovered automatically below `App\Firefiber\Pages`, and the
provider registers its route. Generated components persist state server-side
and are hydrated from signed snapshots; the browser receives only a
session-derived client signing key. Firefiber then works with either Tailwind
CSS v4 or Bootstrap 5.

Destructive operations (`migrate:rollback`, `db:seed`, schema changes in
production) require `--force`. The CLI prints one clear error line instead of
a stack trace.

## Configuration

- `config/*.php` are plain arrays keyed by file name; read them with
  `config()->get('app.name')`. Set `APP_NAME` to change the display name used
  in page titles and navigation; the framework identity remains SahilPHP.
- `bootstrap/app.php` roots the application at the project directory, loads
  `.env` when present, loads `config/`, registers `bootstrap/providers.php`,
  and boots the service providers. `sahil_bootstrap()` returns the same
  application instance for the lifetime of a process.
- Environment variables are listed by `php sahil env:check` and defaulted
  in `config/`. Put local values in `.env` (gitignored) or inject them from
  the environment in production.

## Database

SQLite is the default and needs no setup: the file
`database/database.sqlite` is created on first connect (it is gitignored),
and `DB_DRIVER`/`DB_DATABASE` may be left empty. Boot, routing, views and
`/health` work even before that. Apply the shipped migrations:

```bash
php sahil migrate:status
php sahil migrate
```

The queue and scheduler tables are part of those migrations. To use MySQL or
PostgreSQL instead, set `DB_DRIVER=mysql|pgsql` plus `DB_HOST`, `DB_PORT`,
`DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env` (see
`config/database.php`); the SQLite file is then ignored.

## Security

Production checklist:

- **`APP_KEY`** — generated by `php sahil key:generate`, required before
  using `Sahil\Encryption\Encrypter` (AES-256-GCM + HMAC). Keep it secret and
  unique per installation.
- **`APP_ENV=production` and `APP_DEBUG=false`** — debug output and verbose
  errors are never shown to clients unless debugging is explicitly enabled.
- **Web root is `public/`** — copy `docs/deploy/nginx.conf.example` or
  `docs/deploy/apache-vhost.conf.example`; `public/.htaccess` handles Apache
  rewrites. `.env`, `config/`, `storage/` and `vendor/` must never be
  reachable over HTTP.
- **Secure headers** — `SecurityHeaders` runs for every response
  (`nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`,
  `Permissions-Policy`, `Cross-Origin-Opener-Policy`). Add a CSP through the
  middleware overrides once your asset policy is settled.
- **HTTPS + secure cookies** — set `SESSION_SECURE=true` behind TLS.
- **Queue payloads** — set `queue.allowed_jobs` in `config/queue.php` to the
  job classes you actually dispatch; payloads may then instantiate nothing
  else.
- **Dependencies** — `composer audit` runs in CI; keep packages current.

Report vulnerabilities privately — see [SECURITY.md](SECURITY.md). A static
`public/500.html` is served by the web server when PHP itself fails.

## Tests

```bash
composer test
```

This runs the application test suite (bootstrap, console/CLI, UI commands and
sample-view rendering). Database integration tests in the framework package
are skipped unless `SAHIL_DB_TEST_MYSQL` / `SAHIL_DB_TEST_PGSQL` are set.

## Project layout

| Path | Purpose |
|---|---|
| `app/Http/` | Controllers and middleware (`App\Http`) |
| `app/Models/` | ORM models generated by `unda:model` |
| `app/Policies/` | Authorization policies generated by `unda:policy` |
| `app/Jobs/` | Queued jobs generated by `unda:job` |
| `app/Providers/` | Service providers registered at boot |
| `php sahil` | Minimal CLI entry point (framework commands load from `vendor/`) |
| `bootstrap/app.php` | Application bootstrap (`sahil_bootstrap()`) |
| `bootstrap/providers.php` | Provider manifest |
| `bootstrap/commands.php` | Application command manifest (empty by default) |
| `config/` | Configuration arrays |
| `database/` | Migrations and seeders |
| `public/index.php` | HTTP front controller |
| `public/css`, `public/js` | Hand-written UI kit assets |
| `resources/` | Frontend sources for the Vite build |
| `routes/web.php` | Route table (`/`, `/ui-kit`, `/health`) |
| `routes/auth.php` | Auth pages generated by `unda:auth` (optional) |
| `.sahil/scaffolds/auth/` | Hidden sources for `unda:auth`; copied into `app/`, `routes/` and `resources/views/` on demand |
| `public/logo.png`, `public/logo-mark.png` | Official SahilPHP logo assets |
| `storage/` | Logs, compiled views, sessions, cache, uploads |
| `tests/` | Application test suite (`Tests\`) |
| `views/` | Layouts, pages, partials and profile examples |
| `.sahil-ui.json` | Recorded UI profile (`tailwind` in this checkout) |

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| `The url supplied for the path (../framework) repository does not exist` | Clone the framework repository next to this one (see Installation). |
| CSS/JS links return 404 | Use `php sahil start` (or `composer start -- --port=8088`); starting only the router script leaves static assets unreachable. |
| `/` and `/ui-kit` return `500 {"error":"server_error"}` with `APP_ENV=production` | Build the assets: `php sahil ui:build --clean` (they are gitignored). |
| Pages render unstyled in development | Start Vite (`npm run dev` or `sahil ui:dev`); the layouts point at `http://127.0.0.1:5173`. |
| `ui:status` reports missing sources or packages | Run `php sahil ui:install` (and `npm ci` if packages are missing). |

## Contributing

Development setup, the quality gates and the local-framework workflow are in
[CONTRIBUTING.md](CONTRIBUTING.md); release history is in
[CHANGELOG.md](CHANGELOG.md); community expectations are in
[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

Released under the MIT License. See [LICENSE](LICENSE) for the full text.
