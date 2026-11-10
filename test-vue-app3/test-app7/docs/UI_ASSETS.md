# UI Installation & Asset Commands

**Status:** Frontend integration Phases 5–7.
**Commands:** `ui:install`, `ui:dev`, `ui:build`, `ui:status`, `ui:theme`.
**Implementation:** `Sahil\Ui\*` (`src/Ui/`) and `Sahil\Theme\*`
(`src/Theme/`), wired into `php sahil`.
**Runtime tags:** see [`VIEW_ASSETS.md`](VIEW_ASSETS.md) for the asset manager
and the `@asset_css`, `@asset_js` and `asset()` template helpers.
**Optional theming:** see [`THEMING.md`](THEMING.md) for neutral theme tokens,
Tailwind/Bootstrap mappings, dark mode and accessibility guidance.

The UI commands are deliberately independent of application boot. They read
the project directory (`--path=DIR`, default: the installation root) and can
run in a checkout that has no `.env`, database or booted providers.

---

## 1. Prerequisites

| Tool | Requirement | Why |
|---|---|---|
| Node.js | 20.19+ or 22.12+ | Vite 8 runtime |
| Package manager | npm, pnpm, yarn or bun | installs packages and runs `dev`/`build` scripts |

Detection order:

1. `pnpm-lock.yaml` → pnpm
2. `yarn.lock` → yarn
3. `bun.lockb` or `bun.lock` → bun
4. `package-lock.json` or `npm-shrinkwrap.json` → npm
5. no lock file → first available of npm, pnpm, yarn, bun (npm preferred)

A lock file whose package manager is missing is a hard failure with guidance
(for example `npm install -g pnpm` or `corepack enable pnpm`). The installer
does not silently use a different package manager.

`ui:install` fails before writing when dependencies are requested and Node
or the package manager is missing. Use `--no-install` to scaffold files
anyway; the report still contains actionable prerequisite guidance.

---

## 2. Command reference

```text
php sahil ui:install [profile] [options]
php sahil ui:dev     [options]
php sahil ui:build   [options]
php sahil ui:status  [options]
php sahil ui:theme   [profile] [options]
```

### Options

| Option | Commands | Meaning |
|---|---|---|
| `--profile=NAME` | install, dev, build, theme | `tailwind`, `bootstrap`, `both`, `none` |
| `--path=DIR` | all | project root to operate on |
| `--source=FILE` | theme | neutral token file (default `theme/tokens.json`) |
| `--dry-run` | all | print the plan; write nothing and run nothing |
| `--merge` | install | preserve existing files; merge `package.json` and `.gitignore` |
| `--overwrite`, `--force` | install | replace managed conflicting files after backing them up |
| `--no-install` | install | write files without running the package manager |
| `--clean` | build | remove `public/build` before building |
| `--host=HOST` | dev | pass `--host` to Vite |
| `--port=PORT` | dev | pass `--port` to Vite (1–65535) |
| `--json` | status, theme | emit a machine-readable report |

Profile aliases: `tailwindcss`, `tailwind-css`, `tw`; `bootstrap5`,
`bootstrap-5`, `bs`; `all`, `tailwind+bootstrap`; `plain`, `vanilla`,
`no-framework`.

If `ui:install` is run without a profile, it reuses the profile recorded in
`.sahil-ui.json`. `ui:dev` and `ui:build` also infer the profile from
`.sahil-ui.json` or, failing that, from `package.json` dependencies.

---

## 3. Profiles and generated files

### `tailwind` — Tailwind CSS v4

```
package.json
vite.config.js
resources/css/tailwind.css
```

`vite.config.js` uses the official `@tailwindcss/vite` plugin (`.default`
because the plugin is ESM). `resources/css/tailwind.css` imports
`tailwindcss` and declares `@source` globs for `views/`, `resources/views/`,
`app/` and `resources/css/`. Tailwind v4 is CSS-first: no
`tailwind.config.js` is generated.

Output: `public/build/assets/tailwind.css`.

### `bootstrap` — Bootstrap 5

```
package.json
vite.config.js
resources/css/bootstrap.css
resources/js/bootstrap.js
```

Uses Bootstrap's precompiled CSS and ESM JavaScript. The JS entry imports the
CSS, registers every `data-bs-*` plugin and exposes `window.bootstrap`.
Popper comes from Bootstrap's own dependency; Sass is not required.

Outputs: `public/build/assets/bootstrap.css` and `bootstrap.js`.

### `both`

Union of the Tailwind and Bootstrap files. A single Vite configuration
builds both entry points into the same output directory with distinct names:

```
public/build/assets/tailwind.css
public/build/assets/bootstrap.css
public/build/assets/bootstrap.js
```

### `none` — plain CSS

```
package.json
vite.config.js
resources/css/app.css
```

A small reset plus CSS custom properties, built by Vite with no framework
package. Output: `public/build/assets/app.css`.

### Shared files

- `package.json` — `dev`, `build` and `preview` scripts plus build-time
  `devDependencies`. New file names derive from the target directory.
- `vite.config.js` — `emptyOutDir: false` (so multiple profile builds do not
  delete each other), `cacheDir: storage/framework/vite`, deterministic
  unhashed asset names and a `127.0.0.1:5173` dev server default.
  `server.watch.ignored` keeps `storage/`, `public/build/`, `vendor/`,
  `node_modules/` and `.git/` out of the watcher: logs, sessions, compiled
  views and Vite's own cache are rewritten while the app serves requests, and
  watching them makes the dev server reload the page in a loop.
- `.gitignore` — appends `/node_modules/`, `/public/build/`, `/.vite/`,
  `/storage/framework/vite/` and `/storage/framework/ui-backups/` only when
  missing.
- `.sahil-ui.json` — records `profile`, `generated_at`, `files` and the
  recommended commands. Commit this file; it is how `ui:status` and
  profile-less builds know what was installed.

### Optional theme layer

`ui:theme` is a separate, opt-in step. It reads `theme/tokens.json` (or
`--source=FILE`), validates the neutral token document, and writes generated
files for the installed profile:

```
resources/css/theme.tokens.generated.css
resources/css/theme.tailwind.generated.css       # Tailwind profiles
resources/css/theme.bootstrap.generated.css      # Bootstrap profiles
resources/scss/_sahil-theme.generated.scss       # Bootstrap profiles
resources/css/theme.overrides.css                # created once, user-owned
resources/scss/_sahil-theme.overrides.scss       # Bootstrap, created once
```

It adds a replaceable `@sahil-theme` import block to the profile's existing
entry stylesheet. Generated files are replaced on each run; the token source
and override files are preserved. The command requires `ui:install` first,
does not install packages and does not build assets.

```bash
php sahil ui:theme
php sahil ui:theme --source=theme/brand.json
php sahil ui:theme --dry-run --json
php sahil ui:build
```

See [`THEMING.md`](THEMING.md) for the JSON format, mapping rules, dark-mode
behavior, Bootstrap Sass opt-in and accessibility guidance.

---

## 4. Installation lifecycle

1. Resolve and validate the project directory.
2. Detect Node and the package manager.
3. Build the complete file plan for the selected profile.
4. Read existing `package.json`, validating JSON.
5. Compare every planned file with what is on disk.
6. **Abort with no writes** if conflicts exist and no conflict policy was
   supplied.
7. Apply creates/updates. Existing files replaced by `--overwrite` (and
   `package.json` merged by `--merge`) are copied to
   `storage/framework/ui-backups/<UTC timestamp>-<random>/` first.
8. Write `.sahil-ui.json`.
9. Run `<package-manager> install` unless `--no-install`/`--dry-run`.

An npm failure leaves the generated files in place, prints the tail of the
package manager error and exits nonzero, so the operator can fix the
dependency problem and run `ui:build` without reinstalling the profile.

---

## 5. Conflict policies

| Situation | Default | `--merge` | `--overwrite` |
|---|---|---|---|
| File missing | create | create | create |
| File identical | skip | skip | skip |
| `package.json` differs | abort | add missing keys; existing values win | replace with generated manifest |
| other file differs | abort | preserve existing file, create the rest | back up then replace |
| `.gitignore` lacks entries | append entries | append entries | append entries |
| invalid `package.json` | abort with guidance | abort with guidance | back up then replace |

Merge rules for `package.json`:

- existing `name`, `version`, metadata and dependency versions are kept;
- missing generated scripts and dependencies are added;
- existing scripts are never overwritten (the merge report names preserved
  files so a custom `build` script is visible);
- list values such as `keywords` are kept exactly as the user wrote them;
- semantic comparison ignores key order, so a correctly configured
  `package.json` is not rewritten just because npm sorted it differently.

Use `--merge` for an existing application. Use `--overwrite` only when the
generated configuration should replace a file; the backup path is printed.

---

## 6. Development and production builds

`ui:dev` and `ui:build` execute the project's package scripts:

```text
<pm> run dev -- --host=... --port=... --strictPort
<pm> run build
```

`ui:dev` starts at port 5173 (or the first free port above it), passes
`--strictPort` so Vite cannot move on its own, and records the URL it started in
`public/hot`. The layouts read that file, so a page always points at the Vite
server that is actually running. The file is removed when Vite exits or the
command is interrupted with Ctrl+C.

Before running, they verify:

- the profile is known;
- Node and the package manager exist;
- `package.json` and the relevant scripts exist;
- `node_modules/vite` is installed (unless `--dry-run`).

`ui:dev` attaches the child process to the terminal (`STDIN`/`STDOUT`/
`STDERR`) so Vite's keybindings and hot reload work normally. When the first
port is taken it prints `… is already in use; using N instead` and continues on
the next free port. `ui:build` captures output and prints it after completion.
Both propagate the child's exit code.

After a successful build, `ui:build` verifies every asset path the profile
promises still exists. If a custom Vite config changed the output names, the
command fails with the missing paths rather than reporting a false success.

---

## 7. Status and CI

`ui:status` prints:

- profile and where it was detected;
- Node and package-manager paths/versions;
- lock file;
- generated source files;
- required npm packages;
- expected build outputs with size and timestamp;
- stale outputs (older than their sources);
- one actionable issue line per problem.

Exit codes:

- `0` — profile, sources, packages and fresh build outputs are all present;
- `1` — anything is missing, stale, or tooling is unavailable.

`--json` returns the same data under `ok`, `base_path`, `profile`,
`profile_source`, `toolchain`, `sources`, `packages`, `assets`,
`missing_assets`, `stale_assets` and `issues`.

CI example:

```bash
php sahil ui:install both --no-install   # deterministic file check
npm install
php sahil ui:build
php sahil ui:status --json
```

---

## 8. Process safety

`Sahil\Ui\ProcessRunner` uses `proc_open()` with an argument vector:

```php
$runner->run([$packageManager, 'run', 'build'], $projectPath);
```

There is no shell string, no `exec()`, no `shell_exec()` and no command
concatenation. Arguments containing `;`, `&`, spaces or quotes are passed as
literal argv entries. The `--path`, `--profile`, `--host` and `--port`
values are validated before use; `--port` is constrained to 1–65535. Build
cleanup only deletes `public/build` after confirming it resolves inside the
project root.

---

## 9. Examples

```bash
# Fresh Tailwind v4 project, dependencies installed
php sahil ui:install tailwind
php sahil ui:build
php sahil ui:status

# Existing Bootstrap app with custom Vite config and package.json
php sahil ui:install bootstrap --merge
php sahil ui:status

# Replace a conflicting generated config (backup is kept)
php sahil ui:install both --overwrite

# Inspect a plan without touching the filesystem
php sahil ui:install none --dry-run

# Generate an optional theme for the recorded profile
php sahil ui:theme
php sahil ui:theme --source=theme/brand.json

# Develop on another port
php sahil ui:dev --host=127.0.0.1 --port=5199

# Build another checkout from this installation
php sahil ui:build --path=/srv/apps/example
```

---

## 10. Troubleshooting

| Symptom | Fix |
|---|---|
| `Node.js was not found on PATH` | Install Node 20.19+/22.12+ from nodejs.org or the OS package manager |
| `No package manager was found on PATH` | npm ships with Node; otherwise install pnpm/yarn/bun |
| `The project has [pnpm-lock.yaml] but [pnpm] is not installed` | `npm install -g pnpm` or `corepack enable pnpm` |
| `Missing frontend prerequisites` on install | Install the tools, or use `--no-install` and install later |
| `existing file(s) differ` | Inspect the list, then re-run with `--merge` or `--overwrite` |
| `package.json is not valid JSON` | Fix the JSON, or `--overwrite` to replace it (backup kept) |
| `Frontend dependencies are not installed` | Run `php sahil ui:install` or `<pm> install` |
| `Missing build output(s)` in status | Run `php sahil ui:build` |
| `Build output is older than the source` | Rebuild with `php sahil ui:build` |
| `build succeeded but expected output is missing` | A custom `vite.config.js` changed asset names; align them with the profile or reinstall with `--overwrite` |
| `Cannot add theme imports` | Run `php sahil ui:install <profile>` first; `ui:theme` only edits existing profile entry files |
| `Theme source [...] was not found` | Check `--source`, or omit it to use/create `theme/tokens.json` |
| `not valid JSON` or an invalid token error | Fix the source file; validation happens before any generated file is written |
| Vite dev server port is busy | Nothing to do: `ui:dev` uses the next free port and records it in `public/hot`; `--port=N` only sets the first port to try |

---

## 11. Tests

- `tests/Ui/ProfileTest.php` — profile file/package/asset contracts.
- `tests/Ui/PackageJsonMergerTest.php` — merge and semantic equality.
- `tests/Ui/ProcessRunnerTest.php` — capture, exit codes and shell-injection
  resistance.
- `tests/Ui/ToolchainDetectorTest.php` — lock-file precedence and missing
  prerequisite guidance.
- `tests/Ui/UiInstallerTest.php` — every profile, conflicts, merge,
  overwrite backups, dry-run, idempotency and invalid manifests in temporary
  projects.
- `tests/Ui/UiStatusTest.php` — inference, missing assets, stale assets.
- `tests/Console/UiCommandsTest.php` — end-to-end `php sahil` exit codes and
  output.
- `tests/Console/UiBuildIntegrationTest.php` — real Vite builds in temporary
  projects, including themed builds for every profile. Skipped automatically
  when `node_modules` is not installed.
- `tests/Theme/*` — token validation, framework output, installer behavior
  and preservation rules.
- `tests/Console/UiThemeCommandTest.php` — end-to-end theme CLI, dry-run,
  JSON and custom source handling.
