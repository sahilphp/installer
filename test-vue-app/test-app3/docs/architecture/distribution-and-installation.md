# SahilPHP — Distribution & Installation Architecture

**Status:** Phase 1 assessment (design only — nothing was split, published,
renamed, or implemented).
**Date:** 2026-10-28
**Scope:** Preparing the current repository for public Composer distribution as
three separate concerns:

1. a reusable framework package;
2. an application skeleton;
3. an optional project-creation installer.

Labels used throughout:

- **Existing** — verified in this repository today.
- **Proposed** — designed here; not built.
- **Blocked** — cannot be completed until a prerequisite is fixed.
- **Verified unavailable** — checked against the live service on 2026-10-28.

**No source code, Composer manifest, entry point, test, or package was changed
in this phase.** The audit commands that were run only read the tree or
regenerate local Composer/vendor metadata.

---

## 1. Executive assessment

The repository already has the right *code-level* separation for a framework
package plus application skeleton. Framework code lives under `src/` in the
`Sahil\` namespace; application wiring, configuration, routes, views, and entry
points live in `app/`, `bootstrap/`, `config/`, `routes/`, `views/`, `public/`,
and `bin/`.

That separation is not yet a *distribution* separation:

- the repository is one Composer package with both `Sahil\` and `App\` in its
  PSR-4 map;
- nearly the entire tree is untracked in Git;
- the public HTTP entry point is broken;
- strict PSR-4 autoloading fails;
- the test suite and static analysis are not green;
- no tags, CI, release metadata, or package pages exist;
- the application skeleton is not independently installable.

The split is therefore primarily packaging, entry-point repair, test
partitioning, and release engineering — not a framework rewrite.

| Area | Current verdict |
|---|---|
| Framework/application code separation | **Structurally good**, with two exception paths noted below |
| Composer package identity | **Partially ready** — name already `sahilphp/framework`; manifest mixes framework and application concerns |
| Fresh application installation | **Not possible** — no skeleton package |
| HTTP boot | **CLI boot works; HTTP front controller fails** |
| Test and quality baseline | **Not releasable** — 1 error, 16 failures, 103 PHPStan errors |
| Git release state | **Not releasable** — 1 tracked file, 1 commit, no tags |
| Packagist availability | **None of the proposed packages exist** (verified 404) |

---

## 2. Verified audit results

### 2.1 Commands and actual output

Run from the repository root on 2026-10-28. Environment: PHP 8.4.27, Composer
2.x, with `pdo_mysql`, `pdo_pgsql`, `pcntl`, `mbstring`, `openssl`, and
`fileinfo` loaded.

| Command | Result |
|---|---|
| `composer validate --no-check-publish` | `./composer.json is valid` (exit 0) |
| `composer validate --strict` | `./composer.json is valid` (exit 0) |
| `composer dump-autoload --strict-psr --no-interaction` | **exit 1** — `Class Sahil\Console\Commands\Command located in ./src/Console/Commands/QueueCommand.php does not comply with psr-4 autoloading standard (rule: Sahil\ => ./src). Skipping.`; 3366 classes generated |
| `vendor/bin/phpunit --colors=never` (the same suite as `composer test`) | **exit 2** — **877 tests, 2554 assertions, 1 error, 16 failures, 1 warning, 1 deprecation, 11 PHPUnit notices, 21 skipped**, 30.821 s |
| `composer lint` | **exit 0** — PHP syntax clean across `src/` and `tests/` |
| `composer analyse` | **exit 1** — PHPStan level 6 reports **103 errors** |
| `composer check-style` | **exit 8** — PHP-CS-Fixer dry run reports **6 of 372 files** need style fixes |
| `composer audit` | `No security vulnerability advisories found` |
| `php sahil about` | **exit 0** — `SahilPHP`, environment `development`, PHP 8.4.27, `booted: yes` |
| `php sahil --version` | **exit 0** — `SahilPHP, PHP 8.4.27` |
| `php sahil env:check` | **exit 0** — reports unset optional variables; values are never printed |
| `git ls-files` | **1 tracked file** (`README.md`); the rest of the tree is untracked |
| `git log --oneline` | **1 commit** — `b30c0c9 first commit` |
| `git tag --list` | **no tags** |
| `git remote -v` | `origin https://github.com/Idrisa24/SahilPHP.git` |
| Packagist `p2` endpoint for `sahilphp/framework`, `sahilphp/app`, `sahilphp/installer` | **404 / 404 / 404** — none published (verified 2026-10-28) |
| `https://github.com/Idrisa24/SahilPHP` | **HTTP 200** — the configured remote exists |
| `https://github.com/sahilphp` | **HTTP 404** — the proposed vendor organization does not currently exist |
| Built-in server probe (`php -S 127.0.0.1:8978 public/index.php`) | `GET /health` → **500** `{"error":"server_error"}`; `GET /no-such-page` → **500**; static path → **500** |

There is only **one Composer manifest** in the tree (`composer.json` at the
root). No nested `composer.json` exists in `src/`, `app/`, `tests/`, or any
other directory. A separate npm manifest exists at `package.json`.

### 2.2 Test baseline in detail

The 17 failing/erroring tests break down as follows:

| Area | Failures | Errors | Notes |
|---|---:|---:|---|
| `tests/Http/ApiTest.php` | 11 | 1 | Request-ID, content negotiation, CORS, throttling, structured logging, JSON handling, and method handling are affected. One warning reports `Undefined array key "request_id"` at `ApiTest.php:361`. |
| `tests/Bootstrap/HttpEntryPointTest.php` | 2 | 0 | Both tests receive 500 instead of 200/404 because `public/index.php` does not dispatch. |
| `tests/View/ExpressionTest.php` | 3 | 0 | Stale expected expression strings: expected `$__ctx->get(user.name)` vs generated `$__ctx->get('user.name')`, and the same quoted-path issue for integer keys and identifier lookup. |
| **Total** | **16** | **1** | Plus 1 warning, 1 deprecation, 11 PHPUnit notices, 21 skipped tests. |

The 21 skipped tests are principally database integration tests gated on
`SAHIL_DB_TEST_MYSQL` and `SAHIL_DB_TEST_PGSQL` credentials. The notices are
PHPUnit notices, not necessarily runtime defects, but they must be reviewed
before CI can be made green.

`composer analyse` is also not green. The 103 errors are concentrated in:

- the orphaned `src/Console` engine (PSR-4 violation, class extending a final
  framework class, private-property access, missing types, stale queue APIs);
- `src/Cache` generic and return-type issues;
- `src/Http` CORS, pagination, trusted-proxy, throttle, and request-logging
  typing/call-site issues;
- `src/Testing` docblock/readability issues.

This matters for the package boundary: `src/Console` is not merely a PSR-4
inconvenience; it is dead, inconsistent code that currently fails static
analysis. It should be removed or repaired before the framework is tagged.

The six style findings are in:

- `tests/Examples/IntegrationExampleTest.php`;
- `src/Contracts/Cache/CacheContract.php`;
- `src/Testing/ApplicationTestCase.php`;
- `src/Cache/FileCache.php`;
- `src/Cache/RedisCache.php`;
- `tests/Testing/ApplicationIsolationTest.php`.

### 2.3 What currently boots

CLI boot is working:

```
sahil
  └─ require vendor/autoload.php
  └─ require bootstrap/app.php
       ├─ require vendor/autoload.php
       ├─ Application::configure(dirname(__DIR__))
       ├─ loadEnvironment('.env')      (missing .env is allowed)
       ├─ loadConfiguration('config/')
       ├─ ProviderManifest::load('bootstrap/providers.php')
       └─ register() then boot() every provider
  └─ app services resolved from the container
```

`php sahil about` proves this path works in the current checkout. The HTTP
path does not: `public/index.php` resolves the kernel but never calls it.

---

## 3. Repository inventory

### 3.1 File inventory and ownership

| Path | Current content | Proposed owner | Notes |
|---|---|---|---|
| `src/` | 245 PHP files, `Sahil\` namespace; foundation, HTTP, database, ORM, migrations, validation, view, mail, queue, scheduler, storage, auth, assets, UI installer, theme generator, testing helpers | **Framework** | Includes `src/Ui` (17 files) and `src/Theme` (6 files), which are build-time tools, not runtime dependencies. |
| `src/Support/helpers.php` | Global helpers, guarded with `function_exists` | **Framework** | Already autoloaded via `autoload.files`. |
| `app/` | 17 PHP files: 14 providers + 3 controllers | **Skeleton** | `App\` namespace. |
| `bootstrap/` | `app.php`, `providers.php` | **Skeleton** | Builds the installation-specific application graph. |
| `sahil` | CLI entry point | **Skeleton** | Requires the project's `bootstrap/app.php`; UI commands can run before boot. |
| `public/index.php` | Broken HTTP entry point | **Skeleton** | Must be rewritten before acceptance criteria 3/4 can pass. |
| `public/build/` | 5 built asset files (220–228 KB CSS files, small JS files) | **Skeleton (ignored build output)** | Actual runtime asset output. Not a source of truth. |
| `config/` | 11 PHP files: `app`, `assets`, `auth`, `cache`, `database`, `mail`, `queue`, `schedule`, `session`, `storage`, `view` | **Skeleton** | `Application::loadConfiguration()` requires the directory. |
| `routes/` | `web.php`, `schedule.php` | **Skeleton** | `WebRoutesProvider` loads `routes/web.php`; `ScheduleProvider` loads the schedule file. |
| `database/` | 2 migrations (`jobs`, `failed_jobs`) and seeders directory | **Skeleton** | Application schema, not framework internals. |
| `views/` | 15 templates: layouts, pages, examples, partials, plus a stray `welcome.blade.php` | **Skeleton** | Home page renders `views/pages/home.sahil.php`. |
| `resources/` | Tailwind v4 CSS entry, an unused `resources/views/welcome.blade.php` | **Skeleton (optional frontend)** | The selected UI profile owns its entry points; switching profiles regenerates them. |
| `dist/` | 4 stale CSS files | **Not part of a package** | Not referenced by runtime code; `public/build` is the configured build path. |
| `storage/` | logs, framework cache/views/sessions/schedule, uploads | **Skeleton** | Must ship empty writable directories and ignore generated contents. |
| `tests/` | 92 test files (framework and application-level mixed) | **Split** | Framework tests follow the framework package; application tests follow the skeleton. |
| `docs/` | Architecture and feature documentation | **Both/framework docs** | Dist packaging can exclude non-runtime docs with `.gitattributes`. |
| `package.json`, `vite.config.js` | Frontend build configuration for the selected profile | **Skeleton (optional)** | Node is only needed for optional UI asset commands. Tailwind v4 is CSS-first and needs no `tailwind.config.js` or PostCSS config. |
| `.env.example` | 55 documented environment variables, placeholders only | **Skeleton** | `.env` remains local and ignored. |
| `composer.lock` | Development lock file | **Framework repo (dev); application lock in skeleton** | Applications commit their own lock after the framework is published. |
| `public/storage/` | `.gitkeep` and hardened `.htaccess` | **Skeleton** | Already whitelisted in `.gitignore`. |

### 3.2 Composer and namespace facts

- Package name: `sahilphp/framework` (already set).
- License: MIT; `LICENSE` exists.
- Runtime requirements: `php ^8.2`, `psr/container ^2.0`, `symfony/mailer
  ^7.4`, `symfony/mime ^7.4`.
- Development requirements: `phpunit/phpunit ^12.0`, `phpstan/phpstan ^2.1`,
  `friendsofphp/php-cs-fixer ^3.75`.
- Autoload:
  - `Sahil\` → `src/`;
  - `App\` → `app/` — **framework/application concern mixed into one
    package**;
  - `autoload.files` → `src/Support/helpers.php`;
  - `autoload-dev` `Sahil\Tests\` → `tests/`.
- Scripts: `lint`, `test`, `analyse`, `fix-style`, `check-style`, `check`,
  `start`.
- `composer start` currently is `php -S 127.0.0.1:8000 public/index.php` — it
  neither sets the document root to `public/` nor handles static files.
- `package.json` uses Tailwind 4.3.3, Bootstrap 5.3.8, Vite, and Sass. It
  reports `vite` in the UI profile generator's dependencies; the checked-in
  source tree is **not** a coherent installed UI profile (there is no
  `tailwind.css` or `resources/js/bootstrap.js`, and no `.sahil-ui.json`).
- There is no `main`/`bin` Composer executable declaration; the CLI is a
  project file, which is appropriate for a skeleton command.

### 3.3 PSR-4 and path assumptions

Framework code is almost entirely path-neutral:

- `Application::configure($basePath)` owns `basePath()`, `configPath()`,
  `storagePath()`, and `environmentPath()`.
- `bootstrap/app.php` uses `dirname(__DIR__)` for the project root.
- `sahil` uses `dirname(__DIR__)` for the project root.
- `App\Providers\WebRoutesProvider` and `ScheduleProvider` use
  `dirname(__DIR__, 2)` relative to their own files, which is correct for an
  app provider stored at `app/Providers/*.php`.
- The only `App\` string in `src/` is an error message in `RenderContext.php`;
  the runtime framework does not otherwise reference application namespaces.

Two distribution hazards remain:

1. `src/Testing/ApplicationTestCase.php` defaults its base path to
   `dirname(__DIR__, 2)`. In the current repository that resolves to the
   project root. Once the class is installed under
   `vendor/sahilphp/framework/src/Testing/`, it resolves to the **framework
   package directory**, not the application root. Application tests that
   extend it would boot the wrong directory.
2. `composer.json` puts `App\` in the framework package's `autoload` block.
   That must move to the skeleton's manifest or a framework install will claim
   an application namespace it does not own.

### 3.4 Tests that boot or depend on the installation

At least these test files are application/skeleton tests, not pure framework
tests:

- `tests/Bootstrap/HttpEntryPointTest.php` (starts the real `public/index.php`);
- `tests/Http/ApiTest.php` (requires `bootstrap/app.php`, calls
  `sahil_bootstrap()`);
- `tests/Http/EmitterTest.php` (requires the real bootstrap);
- `tests/Http/UiKitRouteTest.php` (extends `ApplicationTestCase`);
- `tests/View/AssetProviderTest.php` (extends `ApplicationTestCase`);
- `tests/View/SampleViewsTest.php` (renders the real root `views/` directory);
- `tests/Console/SahilCliTest.php` (runs the real `sahil`);
- `tests/Console/SahilCliCommandsTest.php` (extends `ApplicationTestCase`);
- `tests/Console/UiCommandsTest.php` (extends `ApplicationTestCase`, targets
  temporary paths through the real CLI);
- `tests/Console/UiThemeCommandTest.php` (extends `ApplicationTestCase`);
- `tests/Console/UiBuildIntegrationTest.php` (extends `ApplicationTestCase`;
  optionally skipped without Node dependencies);
- `tests/Examples/HttpFeatureExampleTest.php`;
- `tests/Examples/ConsoleExampleTest.php`;
- `tests/Examples/IntegrationExampleTest.php`;
- `tests/Testing/ApplicationIsolationTest.php`.

`tests/Assets/AssetManagerTest.php`, `tests/Theme/ThemeInstallerTest.php`, and
the `tests/Ui/*` temporary-project tests use `UiTestCase`, which creates its
own temporary project. They are framework-level tests even though they exercise
project-facing tools.

The current `tests/` tree contains 92 test files in total. After the split,
the framework repository should keep the unit/integration tests that do not
require an application checkout; the skeleton should keep the application
smoke/acceptance tests listed above.

---

## 4. Distribution blockers and defects

| ID | Severity | Finding | Effect |
|---|---|---|---|
| D1 | **High** | `public/index.php` does not dispatch. It builds a kernel, never calls `handle()`, calls `$this->application->renderView()` from file scope, and `Application::renderView()` does not exist. The catch block converts every path to `{"error":"server_error"}`. | Acceptance criteria 3 and 4 fail; `/health` and 404s return 500. |
| D2 | **High** | The Git repository has only `README.md` tracked. Everything else is untracked. | No release baseline, no diff history, no tag can be trusted. |
| D3 | **High** | `src/Console/Commands/QueueCommand.php` declares `abstract class Command` in namespace `Sahil\Console\Commands`, violating PSR-4. `composer dump-autoload --strict-psr` exits 1. | Strict autoload CI gate fails; the class is not autoloadable. |
| D4 | **High** | Full test suite is not green: 1 error, 16 failures, plus warnings/deprecations/notices. PHPStan reports 103 errors; style check reports 6 files. | No credible release gate; acceptance criterion 7 is not met. |
| D5 | **High** | Test harness base path is wrong once `src/Testing` is installed in `vendor/`. | Fresh-project tests would boot `vendor/sahilphp/framework` rather than the app. |
| D6 | **Medium** | Tests are not partitioned between framework and application. At least 15 files depend on the installation. | A framework-only package cannot run its suite without the skeleton; moving tests after the split is unnecessarily risky. |
| D7 | **Medium** | `composer start` serves without `-t public` and the router does not return `false` for existing static files. | Built-in server sends asset and `.env`-adjacent requests through the front controller; static assets are unreachable. |
| D8 | **Medium** | The checked-in frontend state is not a coherent profile: `package.json` implies both Tailwind and Bootstrap, but sources such as `resources/css/tailwind.css` and `resources/js/bootstrap.js` are absent; `public/build` contains older bootstrap outputs; `dist/` is stale. | A fresh skeleton could fail to render the sample home page or fail `ui:status`. |
| D9 | **Medium** | `.gitignore` omits `/node_modules`, `/dist`, `/public/build`, `storage/framework/sessions/*`, and `storage/framework/cache/*`; session files and 72 MB of `node_modules` are currently untracked. | A careless `git add .` would commit generated assets, dependencies, and session data. |
| D10 | **Low** | Two unused `welcome.blade.php` files exist (`views/` and `resources/views/`) while the configured view extension is `.sahil.php`; `dist/` is stale. | Confusing skeleton contents; accidental shipping of dead templates/build output. |
| D11 | **Low** | No `.gitattributes`, no `.github/`, no `CHANGELOG.md`, `UPGRADE.md`, or `SECURITY.md`; no tags. | Packagist dists would include dev-only files; no release/security process exists. |
| D12 | **Metadata** | The support URLs in `composer.json` already point at the existing `Idrisa24/SahilPHP` repository (HTTP 200). The proposed `sahilphp/*` package names are not published and the `sahilphp` GitHub organization is currently 404. | Package identity/ownership must be decided before publication; do not rename anything in this phase. |

---

## 5. Proposed package boundaries

### 5.1 `sahilphp/framework` — reusable core

**Existing name retained. No namespace rename.**

Proposed package contents:

```
composer.json                 # type: library; Sahil\ only; no App\ autoload
LICENSE
README.md                     # framework-level install/reference documentation
src/                          # all Sahil\ runtime and build-time tooling
src/Support/helpers.php       # autoload.files
src/Ui/                       # ui:install/dev/build/status/theme support
src/Theme/                    # optional theme token generator (build time)
src/Testing/                  # test harness (see base-path fix below)
tests/                        # framework-only tests (dev dependency)
phpstan.neon
.php-cs-fixer.dist.php
phpunit.xml
docs/                         # framework docs; export-ignored from dist if desired
.gitattributes                # export-ignore dev/test/build files
CHANGELOG.md
UPGRADE.md
SECURITY.md
```

Not in the framework package:

- `app/`, `bootstrap/`, `bin/`, `config/`, `database/`, `public/`, `routes/`,
  `views/`, `storage/`, `.env.example`;
- `resources/`, `theme/`, `package.json`, Vite/Tailwind/PostCSS configuration,
  `public/build/`, `dist/`;
- application tests and application fixtures;
- the `start` script, because it serves an application, not the library.

Runtime dependencies remain unchanged: `psr/container`, `symfony/mailer`, and
`symfony/mime`. `src/Ui`/`src/Theme` must not introduce a PHP runtime
dependency: they are invoked only by the project CLI and only write build
files.

**Required pre-extraction fixes:**

- remove or repair the orphaned `src/Console` engine (D3);
- remove `App\` from the framework's `autoload.psr-4`;
- make `ApplicationTestCase` base path explicitly configurable, or move it to
  the skeleton and refactor tests that rely on it (D5);
- finish the framework test/static-analysis/style baseline (D4).

### 5.2 `sahilphp/app` — application skeleton

The skeleton owns application policy and launch files:

```
composer.json                 # sahilphp/app; App\ and Tests\ autoload
composer.lock                 # committed after framework publication
.env.example
.gitignore
README.md
LICENSE
sahil
bootstrap/app.php
bootstrap/providers.php
public/index.php              # corrected HTTP entry point
public/storage/
app/Http/                     # controllers
app/Providers/                # 14 service providers
config/                       # 11 configuration files
routes/web.php
routes/schedule.php
database/migrations/
database/seeders/
views/                        # sample layouts/pages/partials
storage/                      # writable runtime directories
tests/                        # app smoke/feature/CLI tests
resources/                    # optional frontend sources
theme/                        # optional Phase 7 tokens (user-owned)
package.json                  # optional frontend tooling
vite.config.js                # optional, generated for the selected profile
```

Target framework constraint:

```jsonc
{
  "name": "sahilphp/app",
  "type": "project",
  "require": {
    "php": "^8.2",
    "sahilphp/framework": "^1.0"
  },
  "autoload": {
    "psr-4": { "App\\": "app/" }
  },
  "autoload-dev": {
    "psr-4": { "Tests\\": "tests/" }
  },
  "scripts": {
    "start": "php -S 127.0.0.1:8000 -t public public/index.php",
    "test": "phpunit --colors=always",
    "lint": "find app bootstrap config routes tests -name '*.php' -print0 | xargs -0 -n1 php -l"
  }
}
```

Notes:

- The skeleton's tests should use `Tests\` (application namespace), not
  `Sahil\Tests\` (framework test namespace).
- The skeleton should ship a coherent default frontend profile or a
  no-frontend starter. Today's tree is indeterminate (D8). Recommended:
  choose `none` for a minimal PHP starter, or generate one profile with
  `ui:install` before packaging. Do not ship stale `dist/` output.
- If Phase 7 theming is included, commit `theme/tokens.json` and the
  user-owned overrides (`resources/css/theme.overrides.css`,
  `resources/scss/_sahil-theme.overrides.scss`); generated theme files can be
  recreated with `php sahil ui:theme`. Theme output has no runtime
  dependency.
- Do not commit `public/build/`, `node_modules/`, or `dist/`.
- Delete the two unused `welcome.blade.php` files or convert them to a real
  `.sahil.php` example.

### 5.3 `sahilphp/installer` — optional project-creation CLI

A thin Composer wrapper, developed later:

```
sahil new my-project          # preflight + composer create-project
sahil new my-project --no-dev
sahil doctor                  # PHP version + extension preflight
```

Requirements:

- no framework runtime dependency; it must work before a project exists;
- delegate package acquisition to `composer create-project`, not a custom
  downloader;
- refuse a non-empty target unless an explicit interactive confirmation or
  `--force` is supplied;
- offer actionable missing-PHP-extension messages;
- optionally create `.env`, verify writable `storage/`, and run a first
  `doctor`/`about` check.

### 5.4 Repository topology

Two viable options; **recommendation: two repositories**.

- **A (recommended): two repos.** The existing
  `Idrisa24/SahilPHP` repository becomes the framework package, or a new
  `sahilphp/framework` repository is created with extracted history. A separate
  `sahilphp/app` repository holds the skeleton. This is Packagist-native and
  needs no split tooling.
- **B: monorepo + automated split.** Keep one repository and use a
  `splitsh`/subtree-style release process. It preserves one history but adds
  release machinery before there is a release.

The current repository should not be split in Phase 1. If option A is chosen,
the old repository should eventually carry a redirect/README pointing at the
new package home.

---

## 6. Installation experience (target, not current behavior)

### 6.1 Primary workflow

Target:

```bash
composer create-project sahilphp/app my-project
cd my-project
cp .env.example .env
php sahil about
composer start
# then:
curl -fsS http://127.0.0.1:8000/health
```

These commands are **target behavior**. They do not work today because
`sahilphp/app` is not published and the current HTTP entry point is broken.
In-repo equivalents (`composer install`, `php sahil about`) do work.

### 6.2 Minimum steps for a working installation

| Step | Required? | Reason |
|---|---|---|
| `composer create-project sahilphp/app` | **Yes** | Installs framework dependencies, generates the autoloader, and copies the skeleton. |
| `cp .env.example .env` | **Recommended / required for normal deployment** | `Application::loadEnvironment('.env')` tolerates a missing file, but database/mail/session/asset configuration needs values. Production may inject OS environment variables instead. |
| Writable `storage/` directories | **Yes** | `FileLogger` does not create its directory; shipping `.gitkeep` directories is required. `FileSessionDriver` creates session directories, but the skeleton should still ship them. |
| `php sahil migrate` | **Optional for hello-world; required before DB-backed features** | Queue/failed-job/auth tables and any application schema need migrations. |
| `composer install` | **Implicit** | `create-project` runs it. |
| `php sahil ui:install …` / `ui:build` | **Only if using compiled assets** | Node is optional; the framework must boot without it. |
| A key-generation command | **No** | No `APP_KEY` concept exists today. Do not document one. |

### 6.3 Serving

Target `composer start`:

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

`public/index.php` must become a dispatcher:

1. return `false` for an existing static file under `public/` when used as a
   built-in-server router;
2. require `bootstrap/app.php`;
3. build the booted `Application`;
4. resolve `Sahil\Http\Kernel` from the container;
5. create `Request::fromGlobals()`;
6. call `$kernel->handle($request)`;
7. emit through `DefaultResponseEmitter`.

Production uses a real web server whose document root is `public/`; only
`public/` is web-served. Project root, `.env`, `composer.json`, `vendor/`,
`storage/`, and `database/` must never be reachable as static files.

### 6.4 Future installer workflow

```bash
composer global require sahilphp/installer
sahil new my-project
sahil new my-project --no-dev
sahil doctor
```

The installer should orchestrate `composer create-project`, not reimplement
dependency resolution. `composer create-project` already refuses a non-empty
target directory; the installer must add explicit confirmation and must not
delete an existing project without an opt-in flag.

---

## 7. Application skeleton structure

Everything below is justified by the current implementation. Marked entries are
optional unless the application uses the corresponding feature.

```
my-project/
├── .env.example                 # 55 documented variables, no secrets
├── .gitignore                   # vendor, .env, generated runtime, node/build output
├── README.md                    # project-local install/run instructions
├── LICENSE                      # MIT (proposed; inherit the framework license)
├── composer.json                # sahilphp/app; App\ + Tests\
├── composer.lock                # commit in applications for reproducible installs
├── sahil                    # CLI entry point
├── bootstrap/
│   ├── app.php                  # base path, .env, config, providers, boot
│   └── providers.php            # 14 App\Providers
├── public/
│   ├── index.php                # MUST BE CORRECTED — Kernel dispatch + static fallback
│   └── storage/                 # hardened public disk (.gitkeep, .htaccess)
├── app/
│   ├── Http/                    # 3 controllers
│   └── Providers/               # 14 providers
├── config/                      # 11 configuration files
├── routes/
│   ├── web.php                  # required by WebRoutesProvider
│   └── schedule.php             # loaded by ScheduleProvider when present
├── database/
│   ├── migrations/              # jobs + failed_jobs migrations
│   └── seeders/
├── views/                       # 15 sample templates; remove stray .blade files
├── storage/
│   ├── logs/.gitkeep            # FileLogger does not mkdir
│   ├── framework/
│   │   ├── views/.gitkeep
│   │   ├── sessions/.gitkeep    # add ignore coverage
│   │   ├── cache/.gitkeep       # add ignore coverage
│   │   └── schedule/.gitkeep
│   └── uploads/
├── tests/                       # application smoke/feature/CLI tests
├── resources/                   # optional frontend sources
├── theme/                       # optional Phase 7 tokens and overrides
├── package.json                 # optional frontend tooling
└── vite.config.js               # optional, generated for the selected profile
```

Not shipped in the skeleton package:

- `vendor/`, `node_modules/`, `public/build/`, `dist/`;
- `.env`, `storage` runtime contents, compiled views, sessions;
- framework `src/` or framework tests.

### 7.1 How a fresh application boots without copying framework internals

```
vendor/sahilphp/framework/src/...     ← framework, PSR-4 Sahil\ → package src/
vendor/composer/autoload_*            ← generated; loads helpers.php via files
bootstrap/app.php (project)           ← require vendor/autoload.php
                                         Application::configure(project root)
                                         load .env + config/
                                         register bootstrap/providers.php
                                         boot()
public/index.php (project)            ← Request → Kernel::handle → Response → emitter
```

The project owns policy: routes, providers, configuration, views, and
frontend choices. The framework package owns mechanism. Upgrading the framework
is `composer update sahilphp/framework`; framework internals are never copied
into the application.

### 7.2 Generated versus user-owned files

| Path | Ownership | Commit? |
|---|---|---|
| `theme/tokens.json` | User/application | **Yes**, if theming is used |
| `resources/css/theme.overrides.css` | User/application | **Yes** |
| `resources/scss/_sahil-theme.overrides.scss` | User/application | **Yes** |
| `resources/css/theme.*.generated.css` | `ui:theme` | Optional; safe to regenerate |
| `resources/scss/_sahil-theme.generated.scss` | `ui:theme` | Optional; safe to regenerate |
| `public/build/` | Vite | **No** |
| `node_modules/` | npm/pnpm/yarn/bun | **No** |
| `dist/` | Legacy build output | **No** (and remove it) |

If generated theme files are ignored, a fresh checkout must run
`php sahil ui:theme` before building themed assets. If they are committed,
the build is self-contained but duplicated by regeneration.

---

## 8. Package dependency strategy

| Concern | Recommendation |
|---|---|
| Framework constraint in skeleton | `"sahilphp/framework": "^1.0"` — compatible-major range; applications pin exact versions through their own lock file. |
| PHP version | Both packages require `php: ^8.2` (developed against PHP 8.4). Composer's platform check gives a clear installation failure for unsupported PHP. |
| PHP extensions | Composer cannot express an OR (`ext-pdo_mysql` OR `ext-pdo_pgsql`). Put optional extensions in `suggest` (`ext-pdo_mysql`, `ext-pdo_pgsql`, `ext-mbstring`, `ext-openssl`) and add an explicit doctor/preflight check. |
| Lock files | Framework repository: commit `composer.lock` for reproducible development. Skeleton/application: commit `composer.lock` after the framework is published. Packagist consumers resolve their own lock. |
| Configuration defaults | Keep the current model: the skeleton ships `config/*`; framework code uses `config()->get(key, default)` fallbacks. Moving defaults into the framework (Laravel-style publish) is a later ergonomics change, not required for distribution. |
| Framework/application autoload | Framework: `Sahil\` only. Skeleton: `App\` and `Tests\`. Never put `App\` in the framework package. |
| Frontend tooling | Optional, skeleton-owned, Node-only for `ui:*` commands. No Composer runtime package or PHP extension is required for the optional asset pipeline. |
| Theme generator | Build-time only; no runtime dependency. `ui:theme` writes CSS/SCSS files; nothing in the request lifecycle reads its output. |
| Dev tooling | PHPUnit, PHPStan, PHP-CS-Fixer stay in the framework repository. The skeleton gets its own minimal test/lint scripts. |
| Duplicated config drift | Add a skeleton test that asserts every configuration key read by providers exists in the shipped config files. |

---

## 9. Distribution and release requirements

### 9.1 Composer metadata and autoloading

For `sahilphp/framework`:

- keep `name: sahilphp/framework`, `license: MIT`;
- declare `"type": "library"` explicitly;
- keep the existing `support` URLs pointing at a real repository — they
  currently point to `https://github.com/Idrisa24/SahilPHP`, which returned
  HTTP 200; do not replace them with the currently-404 `sahilphp` org until
  ownership is decided;
- remove `App\` from `autoload.psr-4`;
- keep `Sahil\` → `src/` and `autoload.files` for helpers;
- add `suggest` entries for optional extensions;
- add `.gitattributes` with `export-ignore` for tests, docs, scripts, frontend
  sources, build output, and CI files;
- remove the application-serving `start` script from the framework package;
- run `composer validate --strict` and `composer dump-autoload --strict-psr`
  in CI.

For `sahilphp/app`:

- declare `"type": "project"`;
- autoload only `App\` (and `Tests\` in dev);
- require the framework with a compatible constraint;
- expose `start`, `test`, and lint/build scripts for the application;
- commit `composer.lock`.

### 9.2 Versioning and tags

The project has **no tags today**. A proposed ladder:

1. `v1.0.0-alpha.1` after the high-severity defects are fixed and the
   framework can be installed in isolation;
2. `v1.0.0` only after all acceptance criteria below pass;
3. normal SemVer thereafter.

The public compatibility surface is the documented framework API under
`Sahil\`. `App\` is application code and carries no framework compatibility
promise. Add `CHANGELOG.md` (Keep a Changelog style) and `UPGRADE.md` with
major-version migration notes.

### 9.3 Packagist publication

**Verified 2026-10-28: none of the proposed packages is published.**

- `https://repo.packagist.org/p2/sahilphp/framework.json` → 404;
- `https://repo.packagist.org/p2/sahilphp/app.json` → 404;
- `https://repo.packagist.org/p2/sahilphp/installer.json` → 404.

Do not document `composer require sahilphp/framework` or
`composer create-project sahilphp/app` as working until a package page exists
and the command has been verified in an empty directory. Publication requires
a pushed tag, a Packagist account, and control of the `sahilphp` vendor name.
The `sahilphp` GitHub organization currently returns 404; the existing remote
is `Idrisa24/SahilPHP`.

### 9.4 CI (GitHub Actions — none exists; proposed)

Matrix: PHP 8.2, 8.3, and 8.4.

Required gates, in order:

1. `composer validate --strict`;
2. `composer dump-autoload --strict-psr`;
3. `composer lint`;
4. `composer analyse` (PHPStan level 6);
5. `composer check-style`;
6. `composer test`;
7. `composer audit`;
8. optional MySQL/PostgreSQL service job exporting
   `SAHIL_DB_TEST_MYSQL` / `SAHIL_DB_TEST_PGSQL` so the 21 skipped database
   tests run;
9. an E2E job that installs the built package into a temp project and boots
   `/health`.

Current status of those gates:

| Gate | Current |
|---|---|
| `composer validate --strict` | Pass |
| `composer dump-autoload --strict-psr` | **Fail** |
| `composer lint` | Pass |
| PHPStan | **Fail (103 errors)** |
| PHP-CS-Fixer dry run | **Fail (6 files)** |
| PHPUnit | **Fail (1 error, 16 failures)** |
| `composer audit` | Pass (no advisories) |

### 9.5 Compatibility, upgrades, and security

- **Installation compatibility:** Composer handles PHP version failure with a
  platform message. Extensions need a `doctor`/installer preflight because
  Composer has no OR-expression for PDO drivers.
- **Upgrade policy:** SemVer; breaking changes only in a major; deprecate one
  minor before removal where practical; document migrations in `UPGRADE.md`.
- **Security advisories:** add `SECURITY.md` with private reporting,
  GitHub Security Advisories on the canonical repository, and dependency
  updates for `symfony/mailer`, `symfony/mime`, and dev tools. `composer audit`
  is already clean today but must stay in CI.
- **Supply chain:** install from Packagist/tagged packages, not arbitrary
  URLs; commit application lock files; use signed tags if the release process
  can support them.
- **Installer safety:** never overwrite an existing directory without explicit
  confirmation; fail closed on missing required extensions.

---

## 10. Acceptance criteria — current status

| # | Criterion | Today | Future verification |
|---|---|---|---|
| 1 | Framework core installable as a Composer dependency | ❌ Not published (all three package names 404); `App\` is still in the framework autoload | `composer require sahilphp/framework` in an empty directory resolves a tagged release; `Sahil\Foundation\Application` autoloads. |
| 2 | Fresh application without copying the framework repository | ❌ No skeleton package; app and framework live in one tree | `composer create-project sahilphp/app /tmp/demo` succeeds; `/tmp/demo` has no framework `src/` or framework tests. |
| 3 | Application autoloads and boots | ⚠ CLI boot works (`php sahil about` exits 0); HTTP front controller is broken; fresh package boot untested | `php sahil about` exits 0 in a created project; `sahil_bootstrap()` returns a booted `Application`; HTTP entry point resolves through the kernel. |
| 4 | A basic route returns an HTTP response | ❌ `GET /health` returns 500 today; `HttpEntryPointTest` fails | Corrected `public/index.php`; `composer start`; `curl -fsS localhost:8000/health` → 200 JSON. |
| 5 | Tests run from the newly created application | ❌ Not possible yet | `vendor/bin/phpunit` inside a created project exits 0 with the migrated application tests. |
| 6 | Clear failure when PHP version or extensions are missing | ⚠ Composer blocks unsupported PHP; no extension preflight | Lower `require.php` in a branch and observe Composer's platform error; `sahil doctor` prints one actionable line per missing extension and exits non-zero. |
| 7 | Existing framework development and tests remain functional | ❌ 1 error, 16 failures, 103 PHPStan errors, 6 style findings | CI returns green on the recorded framework baseline with no regressions; test counts and baseline are documented per release. |
| 8 | Installation never overwrites an existing project silently | ✅ for Composer's `create-project` behavior; ❌ installer does not exist | `composer create-project` refuses a non-empty target (existing behavior); `sahil new existing-dir` prompts and aborts unless explicitly confirmed. |

---

## 11. Security considerations

- **Secrets:** `.env` is git-ignored and must never enter a package archive;
  `.env.example` contains placeholders only. Do not add credentials to
  fixtures or documentation.
- **Document root:** expose only `public/`. The current `composer start` does
  not set `-t public`; fix that together with the front controller. The
  built-in-server router must return `false` only for existing files under
  `public/`.
- **Repository hygiene:** the first complete commit must be deliberate.
  Ignore `/node_modules`, `/dist`, `/public/build`, runtime sessions/cache,
  logs, and uploads. Today, a single `git add .` would include ~72 MB of Node
  modules and live session files.
- **Testing harness:** ensure application tests cannot accidentally boot the
  framework package directory via `dirname(__DIR__, 2)`.
- **Installer:** refuse non-empty targets; never delete or overwrite without
  explicit confirmation; use Composer/Packagist as the trust boundary.
- **Supply chain:** commit application locks, run `composer audit` in CI,
  maintain private security reporting, and keep dependency-update automation
  enabled.
- **Package integrity:** use `.gitattributes` so dev-only files and tests are
  excluded from Composer dist archives without affecting source installs.
- **Frontend tools:** `ui:*` commands execute user-installed package managers
  through argument vectors; keep that behavior and never construct shell
  strings from profile or path input.

---

## 12. Risks and mitigations

| Risk | Severity | Mitigation |
|---|---|---|
| Broken front controller | **High** | Fix in Phase 2.0; `HttpEntryPointTest` is the regression test. |
| Entire tree untracked | **High** | Make a reviewed first commit before any split; fix `.gitignore` first. |
| PSR-4 violation and dead `src/Console` | **High** | Remove the orphaned console engine or move `Command` to its own file; gate on `--strict-psr`. |
| Test/static-analysis/style debt | **High** | Drive PHPUnit and PHPStan to a documented green baseline; fix style; add CI. |
| Test harness base path in vendor | **High** | Make base path explicit in the skeleton test base class or environment. |
| Application tests mixed with framework tests | **Medium** | Move the 15 application-coupled files during the split; keep framework fixture tests. |
| Packagist/vendor ownership unknown | **Medium** | Confirm `sahilphp` ownership or ship under the existing `Idrisa24`/confirmed vendor; 404 proves “not published”, not “available”. |
| Skeleton frontend profile incoherent | **Medium** | Choose and generate one profile; remove stale `dist/` and old build outputs; document Node as optional. |
| Repo split loses history/URLs | **Medium** | Use history-preserving extraction; keep a redirect README on the old repository. |
| Support/package URLs diverge | **Low** | Decide canonical repository and package vendor before publication; update both manifests. |
| Stale Blade templates and `dist/` | **Low** | Delete or convert; never ship `dist/`. |
| Storage `.gitkeep` / ignore gaps | **Low** | Add keep-files and `.gitignore` rules for sessions, cache, logs, uploads, and build output. |

**Compatibility promise for Phase 1:** package names, namespaces, entry
points, configuration, and test behavior remain unchanged. The only artifact
delivered in this phase is this architecture document.

---

## 13. Implementation phases (proposed — not started)

| Phase | Content | Unlocks |
|---|---|---|
| **2.0 — Repository hygiene** | Reviewed first full commit; fix `.gitignore`; correct `public/index.php`; fix `composer start`; remove/repair `src/Console`; fix test harness base path; establish a green PHPUnit/PHPStan/style baseline; add CI gates. | Criteria 3, 4, 6 (PHP), 7 |
| **2.1 — Partition** | Remove `App\` from framework autoload; extract the skeleton; move the 15 application-coupled tests; add `.gitattributes` export-ignores; choose the skeleton frontend profile; tag the first framework alpha. | Criteria 2, 5 (pending publication) |
| **2.2 — Publish the framework** | Submit `sahilphp/framework` to Packagist; verify `composer require` in an empty directory; generate the skeleton's `composer.lock`. | Criterion 1 |
| **2.3 — Publish the skeleton** | Publish `sahilphp/app`; run the full acceptance script (`create-project` → boot → `/health` → tests) as an automated E2E job. | Criteria 2, 3, 5, 6 (extensions via doctor), 8 (`create-project` half) |
| **2.4 — Installer (optional)** | Build `sahilphp/installer` as a Composer wrapper with `new` and `doctor`; add overwrite confirmation and extension preflight. | Criterion 8 (full), 6 (full) |
| **2.5 — Governance** | `SECURITY.md`, `CHANGELOG.md`, `UPGRADE.md`, README install rewrite, dependency updates, signed-tag evaluation. | Release readiness |

Phase 7 theming is already an optional build-time feature in the existing
tree. It is not a runtime dependency of any package: the skeleton may ship
tokens/overrides, and generated theme files are disposable.

---

## 14. Unresolved decisions requiring approval

| # | Decision | Options | Recommendation |
|---|---|---|---|
| 1 | Repository topology | Two repos vs monorepo + split tooling | **Two repos** — simpler and Packagist-native; no history split in Phase 1. |
| 2 | Package vendor identity | Keep `sahilphp/*` vs use the existing `Idrisa24/SahilPHP` owner | **Keep `sahilphp/*` only if ownership can be claimed**; otherwise use a confirmed vendor. No rename in this phase. |
| 3 | Starting version | `1.0.0-alpha.*` → `1.0.0` vs `0.x` | **Alpha ladder to 1.0.0** after gates pass. |
| 4 | Config defaults location | Skeleton ships all config vs framework ships defaults and publishes them | **Skeleton now**; framework-published defaults later. |
| 5 | Skeleton frontend profile | `none` vs `tailwind` vs `bootstrap` vs `both` | Choose one coherent profile. **Recommend `none` for the minimal starter**, with `ui:install` used to add a framework; never ship an inconsistent mix. |
| 6 | Built asset policy | Commit `public/build` vs ignore and rebuild | **Ignore and rebuild**; do not commit `dist/`. |
| 7 | `ApplicationTestCase` ownership | Framework test utility vs skeleton test base class | **Framework utility with an explicit app base path**; skeleton provides `Tests\TestCase`. |
| 8 | Skeleton `composer.lock` | Commit vs float | **Commit** after the framework is published. |
| 9 | Skeleton license | MIT vs none | **Ship MIT** to match the framework. |
| 10 | Theme files in skeleton | Commit tokens/overrides vs no theme by default | **No theme by default**; when enabled, commit tokens/overrides and regenerate generated files. |
| 11 | Canonical repository/support URL | `github.com/sahilphp/*` vs existing `github.com/Idrisa24/SahilPHP` | Decide before publication; current support URLs point at the real `Idrisa24` repository (HTTP 200), while `github.com/sahilphp` is 404. |

---

## Appendix A — audit command list

```bash
composer validate --no-check-publish
composer validate --strict
composer dump-autoload --strict-psr --no-interaction
vendor/bin/phpunit --colors=never
composer lint
composer analyse
composer check-style
composer audit
php sahil about
php sahil --version
php sahil env:check
git status --short --branch
git ls-files
git tag --list
git log --oneline --decorate
git remote -v
php -S 127.0.0.1:8978 public/index.php   # manual probe: /health, /no-such-page
curl -sS -o /dev/null -w '%{http_code}' \
  https://repo.packagist.org/p2/sahilphp/{framework,app,installer}.json
curl -sS -L -o /dev/null -w '%{http_code}' \
  https://github.com/Idrisa24/SahilPHP
curl -sS -L -o /dev/null -w '%{http_code}' \
  https://github.com/sahilphp
```

## Appendix B — inspection checklist

- **Manifests:** root `composer.json` (only Composer manifest), `package.json`.
- **Autoload:** `Sahil\`/`App\` PSR-4, `src/Support/helpers.php`, strict PSR run.
- **Boot:** `public/index.php`, `bootstrap/app.php`, `bootstrap/providers.php`,
  `sahil`, `app/Providers/WebRoutesProvider.php`, `routes/web.php`,
  `src/Foundation/Application.php`.
- **Configuration/runtime:** `config/*` (11 files), `.env.example` (55
  variables), `storage` tree, `.gitignore`, `public/storage`.
- **Quality:** `phpunit.xml`, `phpstan.neon`, `.php-cs-fixer.dist.php`,
  `scripts/test-databases.sh`.
- **Tests:** full suite, app-coupled test list, `HttpEntryPointTest`.
- **Git/release:** status, tracked files, tags, log, remotes; Packagist and
  GitHub probes.
- **Runtime:** built-in server probe for `/health` and unknown paths; CLI boot
  probe.

## Appendix C — app-coupled test files to move

```
tests/Bootstrap/HttpEntryPointTest.php
tests/Http/ApiTest.php
tests/Http/EmitterTest.php
tests/Http/UiKitRouteTest.php
tests/View/AssetProviderTest.php
tests/View/SampleViewsTest.php
tests/Console/SahilCliTest.php
tests/Console/SahilCliCommandsTest.php
tests/Console/UiCommandsTest.php
tests/Console/UiThemeCommandTest.php
tests/Console/UiBuildIntegrationTest.php
tests/Examples/HttpFeatureExampleTest.php
tests/Examples/ConsoleExampleTest.php
tests/Examples/IntegrationExampleTest.php
tests/Testing/ApplicationIsolationTest.php
```

`tests/Assets/AssetManagerTest.php`, `tests/Theme/ThemeInstallerTest.php`, and
the `tests/Ui/*` tests use `UiTestCase`'s temporary project and may remain
framework-side. The final split should be made after the framework and
skeleton test commands are separated, so a test is moved once, not twice.
