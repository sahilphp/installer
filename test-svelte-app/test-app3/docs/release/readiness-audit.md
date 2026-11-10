# SahilPHP Release Readiness Audit

| | |
|---|---|
| Audit date | 2026-10-28 |
| Repository | SahilPHP working tree (local checkout) |
| Commit audited | `3a3df14bb8927226d74d570ba16c866b13bc73cd` (`The first version 0.0.1`) |
| Branch | `main`, identical to `origin/main` |
| Audit host | PHP 8.4.27, Composer 2.10.3, Node 24.21.0, npm 12.2.0 |
| Scope | Installability, clean boot, test/quality gates, packaging boundaries, secrets, release blockers, prioritized remediation |
| Not done | No publication, push, tag, namespace/package rename, installer implementation, or history rewrite |

## 1. Verdict

**SahilPHP is not release-ready at this commit.**

The framework kernel itself can be booted directly, and a clean Composer install
succeeds on the audit host. However:

- The public HTTP entry point returns **HTTP 500 for every request**.
- **17 tests fail or error**; the suite is not green.
- `composer dump-autoload --strict-psr` fails on a legacy `Sahil\Console` class.
- The root package mixes the framework and the application, so it is not yet a
  clean Composer library.
- The public GitHub repository already contains committed generated dependencies,
  build output, and **142 runtime session payloads**.
- There are no version tags and no Packagist package; the documented installation
  path is still a repository checkout, not a public Composer install.
- The frontend profile is inconsistent: `ui:status` exits 1 because sources and
  expected build outputs are missing.

No source code, Composer manifest, entry point, test, package name, or namespace
was changed during this audit. The only repository change made is a safety-only
`.gitignore` update described in §9. The public repository state and generated
files require a separate, approved remediation.

## 2. Evidence Summary

| Check | Result | Exit | Evidence |
|---|---|---:|---|
| `composer validate --strict` | Pass: `./composer.json is valid` | 0 | Manifest is syntactically and structurally valid |
| `composer check-platform-reqs --no-dev` | Pass on PHP 8.4.27; only `php` and `ext-iconv` reported | 0 | Does not validate database extensions |
| `composer audit` | Pass: no security advisories found | 0 | Current lock file |
| `composer dump-autoload --strict-psr` | **Fail**: `Sahil\Console\Commands\Command` declared in `src/Console/Commands/QueueCommand.php` | 1 | PSR-4 violation |
| `composer lint` | Pass: 372 PHP files, no syntax errors | 0 | `php -l` over `src/` and `tests/` |
| PHPUnit (`vendor/bin/phpunit --colors=never`) | **Fail**: 877 tests, 2,554 assertions, 1 error, 16 failures, 1 warning, 1 deprecation, 11 notices, 21 skipped | 2 | 30.815 s, 34.00 MB |
| `composer analyse` (PHPStan) | **Fail**: 103 errors | 1 | 64 errors are in `src/Console` |
| `composer check-style` (PHP-CS-Fixer) | **Fail**: 6 of 372 files need changes | 8 | Dry run, no files changed |
| Clean Composer install | Pass: 14 runtime packages installed | 0 | Archive of `HEAD`, no `.env` |
| Clean CLI boot | Pass: `php sahil about` reports booted | 0 | `app name: SahilPHP`, `booted: yes` |
| Clean HTTP boot | **Fail**: `/health` → 500, `/no-such-page` → 500 | — | `public/index.php` does not dispatch |
| Direct kernel request | Pass: `/health` → 200 JSON | 0 | Proves the kernel/routing path works |
| Fresh `npm ci` | Pass: 43 packages installed | 0 | Separate clean copy |
| `npm run build` | Pass, with CSS warnings | 0 | Emits `dist/assets/app.css`, not `public/build` |
| `npm run build:bootstrap` | Pass | 0 | Writes `public/build/assets/bootstrap-*.css/js` |
| `php sahil ui:status --json` | **Fail**: profile says `both`; required sources/assets missing | 1 | `ok: false` |
| Packagist `sahilphp/framework` | Not published | — | HTTP 404 |
| Packagist `sahilphp/app` | Not published | — | HTTP 404 |
| Packagist `sahilphp/installer` | Not published | — | HTTP 404 |
| GitHub `Idrisa24/SahilPHP` | **Public** | 200 | GitHub API: `private: false`, `visibility: public` |
| GitHub `sahilphp` organization/user | Not found | — | HTTP 404; vendor namespace is unclaimed |

## 3. Clean Install and Boot

### 3.1 What was tested

A clean copy was created from `git archive HEAD` in the local audit temp
directory, avoiding the working tree's ignored files. The copy contained no
`.env` file. Composer runtime dependencies were installed with:

```bash
composer install --no-interaction --no-dev --prefer-dist
```

Result: **success**, 14 packages installed from `composer.lock`.

`php sahil about` then succeeded:

```text
app name:     SahilPHP
environment:  development
debug mode:   off
timezone:     UTC
php:          8.4.27
booted:       yes
```

### 3.2 HTTP entry point is broken

Starting the built-in server with the documented entry point and requesting
`/health` produced:

```text
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{"error":"server_error"}
```

`/no-such-page` produced the same safe 500. The error body does not leak paths
or stack traces, which is good, but every route is unusable.

The cause is visible in `public/index.php`:

```php
$response = $application->isolatedRequest(function () use ($request): Response {
    return $this->application->renderView('welcome', []);
});
```

`$this->application` is used in file scope, and `Kernel::handle()` is never
called. The resulting `Throwable` is caught by the outer `catch`, which returns
the generic 500. `composer start` uses this same script.

### 3.3 The framework kernel works when invoked directly

A direct request through the container-resolved kernel returned:

```text
status=200 content-type=application/json
body={"status":"ok","framework":"SahilPHP","environment":"development","booted":true,...}
```

This isolates the failure to the front controller/public boot path rather than
the kernel, router, providers, or health controller.

## 4. Test and Quality-Gate Results

### 4.1 PHPUnit

Command used:

```bash
vendor/bin/phpunit --colors=never
```

Final result:

```text
Tests: 877, Assertions: 2554, Errors: 1, Failures: 16, Warnings: 1,
Deprecations: 1, PHPUnit Notices: 11, Skipped: 21.
```

The suite exited with code 2. The skipped tests include environment-dependent
database/integration coverage; they were not treated as passing.

#### Error (1)

| Test | Observation |
|---|---|
| `Sahil\Tests\Http\ApiTest::test_unsupported_method_returns_405_with_allow_header` | `Request::fromGlobals()` throws `MethodNotAllowedHttpException` for `TRACE` before the test's `try` block reaches the kernel. The test expects the kernel to return a 405 response. |

#### Failures (16)

**API/HTTP contract group (11 failures)**

| Test | Observed vs expected |
|---|---|
| `test_malformed_json_body_returns_400_and_does_not_echo_the_body` | Test expects a `BadRequestHttpException`; the kernel returns a response instead. |
| `test_scalar_json_body_returns_400` | Same exception-vs-response mismatch. |
| `test_missing_content_type_with_body_is_allowed_and_empty` | 500 instead of 200. |
| `test_route_that_expects_json_returns_200_with_json_body` | 500 instead of 200. |
| `test_single_request_is_idempotent_and_replays_without_new_side_effects` | 500 instead of 200. |
| `test_request_identifier_is_present_on_every_response_and_unique_per_request` | Missing `X-Request-Id`. |
| `test_content_negotiation_respects_accept_header` | 500 instead of 200. |
| `test_rate_limit_is_bound_and_returns_429_with_retry_after` | 500 instead of 200/429. |
| `test_unknown_path_includes_request_identifier` | Missing request ID. |
| `test_cors_preflight_and_qualified_origin_are_handled` | Missing CORS header. |
| `test_structured_request_logging_writes_one_redacted_event` | Missing request ID in the log event. |

The 500 responses occur on test routes carrying `new Throttle(...)`. `Throttle`
falls back to resolving `StoreInterface` through a `container` request
attribute, but nothing in the request/kernel path sets that attribute. The test
kernel is also constructed without the application's global middleware, so
request-ID/CORS/logging assertions do not exercise the production kernel. This
is a mix of test setup drift and an untested middleware fallback; triage is
required before the suite can be trusted as a release gate.

**Front-controller group (2 failures)**

| Test | Observation |
|---|---|
| `test_health_endpoint_serves_expected_json_response` | 500 instead of 200. |
| `test_unknown_paths_respond_with_safe_404` | 500 instead of 404. |

**View-expression group (3 failures)**

| Test | Observation |
|---|---|
| `test_identifier_lookup` | Expected `$__ctx->get($item)`, actual `$__ctx->get('$item')`. |
| `test_path_with_integer_key` | Expected unquoted path, actual quoted path. |
| `test_dotted_path` | Expected unquoted path, actual quoted path. |

`ExpressionCompiler` deliberately quotes identifier paths (`var_export(...)`)
for safe compiled PHP. The three tests appear to encode an older contract and
need to be reconciled with the implementation rather than assumed to be product
failures.

#### Warning and notices

- One warning at `tests/Http/ApiTest.php:361`: undefined array key `request_id`.
- 11 PHPUnit notices and 1 deprecation were reported; these were not suppressed.
- 21 tests were skipped, so the 877-test suite is not full coverage of
  database/integration behavior.

### 4.2 PHPStan

`composer analyse` exited 1 with **103 errors**. The largest concentration is
the orphaned legacy console layer:

| File | Errors |
|---|---:|
| `src/Console/Application.php` | 11 |
| `src/Console/Commands/DispatchCommand.php` | 8 |
| `src/Console/Commands/ListenCommand.php` | 7 |
| `src/Console/Commands/FailCommand.php` | 6 |
| `src/Console/Commands/FailedCommand.php` | 6 |
| `src/Console/Commands/FailedJobCommand.php` | 6 |
| `src/Console/Commands/RunCommand.php` | 6 |
| `src/Console/Commands/WorkCommand.php` | 6 |
| `src/Console/Input.php` | 6 |
| `src/Console/Commands/QueueCommand.php` | 2 |
| Other files | 39 |

Of the 103 errors, **64 are under `src/Console`**. `src/Console/Application.php`
extends the final `Sahil\Foundation\Application` and accesses its private
`$container`. `sahil` does not use `Sahil\Console`; it uses the separate
command dispatch in the CLI entry point and `Sahil\Ui\UiConsole`. This layer
appears to be dead and should be removed or rewritten.

Other clusters include missing iterable/property types in
`Support/Logging/RequestLogger.php`, `Http/Cors/Config.php`,
`Http/TrustedProxy.php`, and the testing harness.

### 4.3 PHP-CS-Fixer

`composer check-style` exited 8: **6 of 372 files** need fixes.

```text
src/Contracts/Cache/CacheContract.php
src/Testing/ApplicationTestCase.php
src/Cache/FileCache.php
src/Cache/RedisCache.php
tests/Testing/ApplicationIsolationTest.php
tests/Examples/IntegrationExampleTest.php
```

### 4.4 Composer autoload

`composer dump-autoload --strict-psr` emitted:

```text
Class Sahil\Console\Commands\Command located in ./src/Console/Commands/QueueCommand.php
does not comply with psr-4 autoloading standard (rule: Sahil\ => ./src). Skipping.
```

The file `src/Console/Commands/QueueCommand.php` declares
`abstract class Command` while its path maps to `Sahil\Console\Commands\QueueCommand`.
A clean `composer install` also prints this warning while generating optimized
autoload files, so consumers see it before any application code runs.

## 5. Blockers

### P0 — Must be resolved before any public release

1. **Public repository already exposes tracked runtime/generated files.**
   `origin/main` equals audited `HEAD`, and GitHub reports `private: false`.
   The public tree contains 142 tracked session files, 1,303 `node_modules`
   files, 5 `public/build` files, and 4 `dist` files. This is a pre-existing
   condition; this audit did not push, tag, or publish anything.
   - The session files are JSON payloads with `_last_activity` and `_token`
     fields. Values were not printed. They appear to be anonymous/test session
     records, not user credentials, but they are still session identifiers and
     CSRF token material and must not be public.
   - No `.env`, private key, API token, or non-empty credential placeholder was
     found in tracked content or any commit. That does not undo the session
     exposure.
   - Required containment: remove runtime/generated files from the index,
     rewrite history or start a clean repository, invalidate all existing
     sessions, and rotate any token that could have been exposed. Keep the
     repository private until this is complete.

2. **The HTTP front controller is non-functional.**
   Every request returns a safe 500. A public application cannot be installed
   or demonstrated until `public/index.php` boots the kernel, handles static
   files for the built-in server, dispatches the request, and emits the response.

3. **The package boundary is not installable as a framework package.**
   `composer.json` is named `sahilphp/framework` but autoloads both `Sahil\` and
   `App\`, and the repository also contains the application, routes, views,
   bootstrap, CLI, and application tests. `composer dump-autoload --strict-psr`
   fails. A consumer cannot install the framework alone, and an application
   skeleton cannot own its `App\` namespace until the split is done.

4. **The test harness base path will break after extraction.**
   `src/Testing/ApplicationTestCase.php` uses `dirname(__DIR__, 2)` as its base
   path. Once installed at `vendor/sahilphp/framework/src/Testing`, that points
   at the framework package, not the consuming application. Application tests
   would boot the wrong tree.

### P1 — Required for a credible first release

1. **The test suite is not green.** 1 error and 16 failures, plus a warning,
   deprecation, and notices. `composer check` cannot pass.
2. **PHPStan and style gates fail.** 103 PHPStan errors and 6 style violations.
3. **No versioning or public distribution.** There are 0 tags, no Packagist
   package, and no `composer require`/`composer create-project` path. README
   installation is still `composer install` from a checkout.
4. **PHP version compatibility is inconsistent.** The manifest says `php: ^8.2`,
   but the Lock file resolves `symfony/event-dispatcher v8.1.5` (requires PHP
   >= 8.4.1), and the dev toolchain uses PHPUnit 12 (requires PHP >= 8.3) and
   Symfony 8 components (PHP >= 8.4.1). `composer prohibits php 8.2` confirms
   the conflict. Either support 8.2 with a compatible lock/toolchain, or raise
   the documented and declared minimum.
5. **Frontend state is incoherent.** `ui:status` exits 1:
   - profile is detected as `both` from `package.json` dependencies;
   - `resources/css/tailwind.css` and `resources/js/bootstrap.js` are absent;
   - `public/build/assets/tailwind.css`, `bootstrap.css`, and `bootstrap.js`
     are absent;
   - `vite.config.js` builds `resources/css/app.css` into `dist/`, while the
     asset manager expects `public/build/`;
   - `vite` is not a direct `package.json` dependency (it is currently pulled
     in as a peer of `@tailwindcss/vite`);
   - `npm run build` succeeds but emits unknown `@tailwind`/`@apply` warnings.
6. **The Throttle request-store path is unverified/broken when used directly.**
   `Throttle` demands a `container` attribute that the request/kernel path does
   not set. The application provider binds a store-backed instance for
   container resolution, but the test route middleware is constructed directly.
   This needs a single supported usage pattern and a regression test.

### P2 — Release quality and documentation

1. Missing release/governance files: `.gitattributes`, `.github/` CI,
   `CHANGELOG.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`.
2. Hard-coded developer assumptions:
   - `scripts/test-databases.sh` hard-codes `/tmp/opencode`,
     `/usr/lib/postgresql/18`, and a `jinx` database user in its example.
   - `tests/Bootstrap/HttpEntryPointTest.php` hard-codes fixed port 8931 and
     `/tmp/opencode` log paths.
   - README and database integration test comments use a `jinx` example user.
   - Local log files under `storage/logs/` contain absolute workstation paths;
     they are ignored, but should never be published.
3. Runtime extension requirements are not declared in `composer.json`.
   README says `pdo_mysql`/`pdo_pgsql`, optional `mbstring`, and `openssl` for
   TLS are required. `composer check-platform-reqs` only reports `ext-iconv`
   and PHP because no extension requirements are declared.
4. `src/Console` is dead, PSR-4-invalid, and contributes most PHPStan errors.
5. `public/index.php` has no static-file bypass for PHP's built-in server;
   `composer start` does not set a document root.
6. Documentation says `curl /health` works and the core is implemented, but the
   audited commit returns 500.
7. Build output is committed (`dist/`, `public/build/`) instead of being built
   and published as an artifact.

## 6. Security and Secret Review

### 6.1 What was checked

- Working tree `.env` presence.
- `.env*` files in the current tree and all Git history.
- Private key files (`*.pem`, `*.key`, `*.p12`, `*.pfx`, `id_rsa*`), database
  dumps/snapshots, credential/secret-named files.
- Private-key headers, AWS access-key IDs, GitHub personal-access-token
  prefixes, Slack token prefixes, and Stripe live-key prefixes across tracked
  content.
- Non-empty credential-like values in `.env.example` (names only; values were
  not printed).
- Runtime session payload field names (values were not printed).
- GitHub visibility and the relationship between local `HEAD` and `origin/main`.

### 6.2 Findings

- **No `.env` file is present** in the working tree or Git history. Only
  `.env.example` is tracked.
- **No private-key or token signature was found** in any tracked content.
- **No non-empty `PASSWORD`/`SECRET`/`TOKEN`/`KEY` value was found** in
  `.env.example`.
- **No credential file names** were found in Git history beyond `.env.example`.
- **142 session payload files are tracked and already public.** They are JSON
  objects with `_last_activity` and `_token` keys. Values were deliberately not
  reproduced. Treat every session as invalidated and remove the files from
  history before reusing this repository publicly.
- The audit itself created one additional untracked session file by issuing a
  direct kernel request. It is now covered by `.gitignore` and was not committed.
- The public GitHub state is material: the repository is not merely "about to be
  published." Local `HEAD` and `origin/main` are the same commit, and GitHub
  reports `private: false`. No remote modification was made by this audit.

### 6.3 Secret-handling conclusion

No application credentials, API tokens, private keys, or production database
credentials were found in tracked content; database configuration is
environment-driven. The release is nevertheless **not safe to keep public**
because committed session/token material and generated dependency/build trees
are exposed. History cleanup and session invalidation are required, not just
new ignore rules.

## 7. Package and Distribution Readiness

### 7.1 Current manifest

```json
{
  "name": "sahilphp/framework",
  "description": "The Sahil Framework.",
  "license": "MIT",
  "require": {
    "php": "^8.2",
    "psr/container": "^2.0",
    "symfony/mailer": "^7.4",
    "symfony/mime": "^7.4"
  },
  "autoload": {
    "psr-4": {
      "Sahil\\": "src/",
      "App\\": "app/"
    },
    "files": ["src/Support/helpers.php"]
  }
}
```

The name says framework, but the autoload and repository layout include the
application. The framework package must not own `App\`, `app/`, `bootstrap/`,
`config/`, `database/`, `public/`, `routes/`, `views/`, or the application
tests. Conversely, the application skeleton must not copy framework source.

### 7.2 Versioning and publication

- No Git tags: 0.
- No Packagist packages: all three checked names return 404.
- No installer package: `sahilphp/installer` is not published.
- `composer install` in a checkout emits `Composer could not detect the root
  package (sahilphp/framework) version, defaulting to '1.0.0'`.
- README currently documents only `composer install`; it cannot yet document
  `composer require sahilphp/framework` or
  `composer create-project sahilphp/app` as working.
- Support URLs point to `https://github.com/Idrisa24/SahilPHP`, while the
  `sahilphp` vendor namespace has no GitHub organization (404). The vendor name
  should be secured or renamed before Packagist publication.

### 7.3 Recommended package direction

Keep the name `sahilphp/framework` for the reusable core, but split ownership:

| Package/repository | Owns |
|---|---|
| `sahilphp/framework` | `src/`, framework helpers, framework tests/docs, build-time UI/theme tooling |
| `sahilphp/app` (skeleton) | `app/`, `bootstrap/`, `bin/`, `config/`, `database/`, `public/`, `routes/`, `views/`, `storage/`, application tests, optional frontend files |
| `sahilphp/installer` (optional, deferred) | `sahil new` / `sahil doctor` wrapper around `composer create-project` |

This matches the existing architecture document and preserves the current
`Sahil\` namespace. Do not rename packages or namespaces as part of this audit.

## 8. Runtime and Dependency Notes

### 8.1 Declared versus observed

- `composer require` declares only PHP, `psr/container`, `symfony/mailer`, and
  `symfony/mime`.
- `composer check-platform-reqs --no-dev` reports only `php` and `ext-iconv`
  on the audit host.
- README additionally requires one of `pdo_mysql`/`pdo_pgsql`, optionally
  `mbstring`, and `openssl` for TLS SMTP.
- The audit host had `pdo`, `pdo_mysql`, `pdo_pgsql`, `mbstring`, `openssl`,
  `iconv`, `fileinfo`, `pcntl`, `posix`, `intl`, `redis`, and `curl`; this
  proves nothing about a consumer's environment.
- `pcntl` is feature-detected by `Scheduler/TimeoutGuard`; it is not a hard
  boot requirement.
- `mbstring` is used directly by `Validator`, but `symfony/polyfill-mbstring`
  is installed transitively, so the effective behavior depends on the
  dependency graph rather than a declared extension requirement.

### 8.2 PHP 8.2 claim

The manifest claims `^8.2`, but:

```text
symfony/event-dispatcher v8.1.5 requires php >=8.4.1
phpunit/phpunit 12.5.42 requires php >=8.3
symfony/console v8.1.9 requires php >=8.4.1
```

`composer prohibits php 8.2` confirms the lock/toolchain conflict. On the audit
host (PHP 8.4.27) the install works; that is not evidence for PHP 8.2. The
project must either:

- lower/pin dependencies and dev tooling for PHP 8.2, or
- raise `require.php`, README requirements, and CI matrix to the real minimum.

### 8.3 Frontend dependencies

- Fresh `npm ci` succeeded: 43 packages.
- `vite` is installed as a peer dependency of `@tailwindcss/vite`, not declared
  directly in `package.json`.
- `npm run build` succeeds but builds `resources/css/app.css` to `dist/` and
  warns about unprocessed `@tailwind`/`@apply` rules.
- `npm run build:bootstrap` succeeds and writes Bootstrap files under
  `public/build/assets/`.
- `ui:status` still exits 1 because the detected `both` profile's required
  sources and expected asset names are missing.

## 9. Repository Changes Made During This Audit

Only one repository file was edited: `.gitignore`.

Added rules:

```gitignore
/node_modules/
/.env.*
!/.env.example
/dist/
/public/build/
/storage/framework/sessions/*
!/storage/framework/sessions/.gitkeep
/storage/framework/cache/*
!/storage/framework/cache/.gitkeep
```

Reason: prevent future accidental commits of dependencies, environment files,
build output, sessions, and cache. `.env.example` remains tracked.

Important limitation: `.gitignore` does **not** untrack files that are already
tracked. The 1,303 `node_modules` files, 5 `public/build` files, 4 `dist` files,
and 142 session files remain in the current commit and in public history. They
must be removed from the index and history separately. No index removal,
history rewrite, commit, tag, or push was performed.

The audit document itself is a new untracked file until the owner commits it.

## 10. License Recommendation

The repository already contains an MIT `LICENSE` (copyright 2026 Idrisa John)
and `composer.json` declares `"license": "MIT"`.

**Recommendation: retain MIT after owner approval.** MIT is the most suitable
choice for a framework seeking broad adoption, commercial use, and ecosystem
contributions. It is compatible with the Symfony components and other current
dependencies.

If explicit patent protection is a priority, Apache-2.0 is the main alternative,
but it adds notice obligations and patent-termination terms. Do not change the
license or add a new license without explicit approval. Confirm the copyright
holder name before the first public tagged release.

## 11. Prioritized Remediation Plan

### Phase 0 — Containment (before any further publication)

1. Keep the repository private/frozen until the public tree is clean.
2. Invalidate all existing file sessions; rotate any token that could have been
   exposed; review the public repository's commit history.
3. Remove `node_modules/`, `dist/`, `public/build/`, and
   `storage/framework/sessions/` from the index. Do not delete working copies
   blindly.
4. Rewrite history or start a fresh repository from a clean tree. A commit that
   merely untracks the files leaves them in public history.
5. Confirm no `.env`, key, credential, session, or production DB data remains in
   the rewritten history.

### Phase 1 — Restore a green baseline (must precede package split)

1. Fix `public/index.php`: dispatch through `Kernel::handle()`, emit the response,
   and handle static files for the built-in server. Add an HTTP regression test.
2. Remove or rewrite `src/Console`; fix the `QueueCommand.php` class/file
   mismatch; make `composer dump-autoload --strict-psr` pass.
3. Fix the API test harness/API contract drift; make Throttle store resolution
   explicit and test the real production kernel composition.
4. Reconcile the three ExpressionCompiler tests with the intended safe output.
5. Make the test suite green; fix PHPStan and PHP-CS-Fixer; add CI that runs
   `lint`, `test`, `analyse`, `check-style`, and `dump-autoload --strict-psr`.
6. Fix `ApplicationTestCase` so its base path comes from the consuming app, not
   from the framework package location.

### Phase 2 — Package split and clean install

1. Extract the framework package (`Sahil\` only; no `App\`, no application
   bootstrap/routes/views).
2. Create the application skeleton under `sahilphp/app`; it owns `App\` and all
   runtime application files.
3. Move framework tests/docs with the framework; move application tests with
   the skeleton.
4. Add `.gitattributes` export-ignore rules and a clean Composer archive.
5. Verify `composer create-project`/`composer require` in an empty directory,
   then boot `/health` and run the application test suite from `vendor/`.

### Phase 3 — Release engineering

1. Decide the real PHP minimum and make manifest, lock, README, CI, and
   composer platform configuration agree.
2. Add `SECURITY.md`, `CHANGELOG.md`, `CONTRIBUTING.md`,
   `CODE_OF_CONDUCT.md`, and issue/PR templates.
3. Add release CI, signed/annotated semver tags, and a publish checklist.
4. Publish `sahilphp/framework` and the skeleton only after the clean-history and
   green-suite gates pass.
5. Secure the `sahilphp` Packagist vendor namespace and GitHub organization, or
   rename to a namespace actually controlled by the maintainer.

### Phase 4 — Frontend and runtime polish

1. Choose one coherent default frontend profile for the skeleton (the prior
   architecture recommendation is `none` for the minimal starter).
2. Make `ui:status` exit 0 for the supported profiles.
3. Declare `vite` directly; make `vite.config.js` write to `public/build` or
   make the asset manager agree on the output path.
4. Add a supported-profile build test to CI.
5. Replace developer-specific database/script paths with environment variables
   and documented defaults.

## 12. Release Gate Checklist

A release should not be tagged until all of these are true:

- [ ] Public tree/history contains no `.env`, keys, tokens, sessions,
      `node_modules`, `dist`, or `public/build`.
- [ ] `composer validate --strict` passes.
- [ ] `composer install --no-dev` succeeds on every supported PHP version.
- [ ] `composer dump-autoload --strict-psr` passes.
- [ ] `composer audit` is clean.
- [ ] `composer lint`, `composer test`, `composer analyse`, and
      `composer check-style` pass.
- [ ] PHPUnit has 0 errors/failures on the supported matrix (or a documented,
      approved skip policy).
- [ ] Clean CLI boot: `php sahil about` and `php sahil --version`.
- [ ] Clean HTTP boot: `/health` returns 200; an unknown path returns 404;
      static assets are served.
- [ ] Application skeleton boots from `vendor/sahilphp/framework`; test base
      path resolves to the application, not the framework package.
- [ ] `ui:status` exits 0 for the documented default profile; `npm ci` and the
      supported build command pass from a clean install.
- [ ] A semver tag exists and the Packagist package page resolves.
- [ ] `README` documents only commands that actually work for the published
      package.
- [ ] MIT license and copyright holder are confirmed and approved.
- [ ] `SECURITY.md`, changelog, contribution guide, CI, and `.gitattributes`
      are present.

## 13. Decisions Requiring Approval

1. Approve a clean-history strategy (rewrite or fresh repository) and the
   session-invalidation step.
2. Approve the package split described in §7.3 and the framework/skeleton
   boundary.
3. Approve the PHP minimum: keep PHP 8.2 with compatible dependency pins, or
   raise to PHP 8.3/8.4.
4. Approve the license recommendation (retain MIT) and copyright holder.
5. Approve the default frontend profile for the skeleton.
6. Approve whether `sahilphp/installer` remains deferred.

## Appendix A — Commands Run

```bash
git status --short --branch
git log --oneline --decorate -6
git ls-files
git log --all --name-only --pretty=format:
composer validate --strict
composer check-platform-reqs --no-dev
composer audit --no-interaction
composer dump-autoload --strict-psr --no-interaction
composer lint
vendor/bin/phpunit --colors=never
composer analyse
composer check-style

git archive HEAD | tar -x -C "$AUDIT_DIR"
composer install --no-interaction --no-dev --prefer-dist
php sahil about
php -S 127.0.0.1:8932 public/index.php
curl -i http://127.0.0.1:8932/health
curl -i http://127.0.0.1:8932/no-such-page
php -r '...direct Kernel /health request...'
php sahil ui:status --json
npm ci --no-audit --no-fund
npm run build
npm run build:bootstrap
composer prohibits php 8.2
```

## Appendix B — Test Failure Log

The full PHPUnit log from the audit is stored in the local audit temp directory.
It is not part of the repository and is not needed for publication.

The complete failing/error test names are:

```text
Sahil\Tests\Http\ApiTest::test_unsupported_method_returns_405_with_allow_header (error)
Sahil\Tests\Http\ApiTest::test_malformed_json_body_returns_400_and_does_not_echo_the_body
Sahil\Tests\Http\ApiTest::test_scalar_json_body_returns_400
Sahil\Tests\Http\ApiTest::test_missing_content_type_with_body_is_allowed_and_empty
Sahil\Tests\Http\ApiTest::test_route_that_expects_json_returns_200_with_json_body
Sahil\Tests\Http\ApiTest::test_single_request_is_idempotent_and_replays_without_new_side_effects
Sahil\Tests\Http\ApiTest::test_request_identifier_is_present_on_every_response_and_unique_per_request
Sahil\Tests\Http\ApiTest::test_content_negotiation_respects_accept_header
Sahil\Tests\Http\ApiTest::test_rate_limit_is_bound_and_returns_429_with_retry_after
Sahil\Tests\Http\ApiTest::test_unknown_path_includes_request_identifier
Sahil\Tests\Http\ApiTest::test_cors_preflight_and_qualified_origin_are_handled
Sahil\Tests\Http\ApiTest::test_structured_request_logging_writes_one_redacted_event
Sahil\Tests\Bootstrap\HttpEntryPointTest::test_health_endpoint_serves_expected_json_response
Sahil\Tests\Bootstrap\HttpEntryPointTest::test_unknown_paths_respond_with_safe_404
Sahil\Tests\View\ExpressionTest::test_identifier_lookup
Sahil\Tests\View\ExpressionTest::test_path_with_integer_key
Sahil\Tests\View\ExpressionTest::test_dotted_path
```
