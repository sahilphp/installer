# Views & Asset Integration

**Status:** Frontend integration Phases 6–7.
**Runtime service:** `Sahil\Assets\AssetManager` (`src/Assets/`).
**Contract:** `Sahil\Contracts\Assets\AssetManagerContract`.
**View integration:** `ViewEngine` → `RenderContext` → `@asset_*` / `asset()`.
**Commands:** see [`UI_ASSETS.md`](UI_ASSETS.md).
**Optional theme tokens:** see [`THEMING.md`](THEMING.md).

The asset manager is the runtime counterpart to `ui:install`, `ui:dev` and
`ui:build`. Templates never construct `public/build/...` paths by hand: they
ask for an entry point and the manager chooses the development-server URL or
the production build URL.

---

## 1. Selecting a UI profile

Install exactly one profile per application:

```bash
php sahil ui:install tailwind
php sahil ui:install bootstrap
php sahil ui:install both
php sahil ui:install none
```

`ui:install` writes `.sahil-ui.json`, which records the profile and generated
files:

```json
{
  "profile": "tailwind",
  "files": ["package.json", "vite.config.js", "resources/css/tailwind.css"]
}
```

The runtime resolves the profile in this order:

1. `ASSET_PROFILE` / `assets.profile` when it is not `auto`;
2. `.sahil-ui.json`;
3. `package.json` dependencies (`tailwindcss`, `@tailwindcss/vite`,
   `bootstrap`, `vite`).

Use `auto` (the default) unless an application must override the recorded
profile. A Tailwind-only profile cannot request Bootstrap entry points, so a
framework is never loaded implicitly.

| Profile | CSS entry points | JS entry points |
|---|---|---|
| `tailwind` | `tailwind` | — |
| `bootstrap` | `bootstrap` | `bootstrap` |
| `both` | `tailwind`, `bootstrap` | `bootstrap` |
| `none` | `app` | — |

`both` is the only profile that emits two CSS frameworks, and only because
the developer selected it explicitly.

After installing a profile, the optional neutral theme layer can be generated
with `php sahil ui:theme`. It changes CSS custom properties and framework
theme mappings, not the asset entry-point contract above; layouts keep using
the same `@asset_*` helpers. See [`THEMING.md`](THEMING.md).

---

## 2. Configuration

`config/assets.php` reads:

| Variable | Default | Meaning |
|---|---|---|
| `ASSET_PROFILE` | `auto` | Override the recorded/inferred profile |
| `ASSET_MODE` | `auto` | `development` or `production`; `auto` follows `APP_ENV` |
| `ASSET_DEV_SERVER` | `http://127.0.0.1:5173` | Vite dev server origin |
| `ASSET_URL` | empty | Public URL prefix for a subdirectory or CDN |
| `ASSET_VERIFY` | `true` | Fail production rendering if a build output is missing |

Filesystem/build paths can be changed in `config/assets.php` with
`build_path` and `build_public_path`; the defaults are `public/build` and
`build`.

`ASSET_MODE=production` points tags at:

```text
/build/assets/<entry>.css
/build/assets/<entry>.js
```

When `ASSET_VERIFY` is enabled, a missing file throws an actionable
`Sahil\Assets\AssetException` (wrapped by the view engine as
`ViewRenderException`) instead of producing a broken 404 link.

---

## 3. Template helpers

### Entry tags

```php
@asset_client
@asset_css('main')
@asset_js('main')
```

| Helper | Output |
|---|---|
| `@asset_client` | Vite HMR client in development; empty in production |
| `@asset_css('main')` | The active profile's stylesheet tag(s) |
| `@asset_js('main')` | The active profile's module script tag(s), or empty |

`@asset_css` and `@asset_js` accept an optional attributes array:

```php
@asset_css('main', ['media' => 'print'])
@asset_js('main', ['nonce' => nonce, 'crossorigin' => 'anonymous'])
```

Attribute names are validated, values are HTML-escaped, and event-handler
attributes (`onload`, `onerror`, ...) are rejected. `rel`/`href` on a
stylesheet and `src`/`type` on a script are managed by the manager and cannot
be overridden.

### Public files

```php
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
<script src="{{ asset('js/reports.js') }}" defer></script>
<img src="{{ asset('images/logo.png') }}" alt="Logo">
```

`asset()` returns a URL for a file under `public/`:

- segments are percent-encoded;
- absolute URLs, protocol-relative URLs, query strings and `..` traversal are
  rejected;
- the default output is root-relative (`/css/reports.css`);
- `ASSET_URL=/app` produces `/app/css/reports.css`.

The normal escaped echo `{{ asset(...) }}` is correct for URL attributes.
There is no raw-output requirement.

### Explicit entries

`main` is the default and should be used by layouts. Explicit entries are
available for pages that intentionally use a framework entry:

```php
@asset_css('tailwind')   {{-- only when the active profile includes Tailwind --}}
@asset_css('bootstrap')  {{-- only when the active profile includes Bootstrap --}}
@asset_css('app')        {{-- only for the `none` profile --}}
@asset_js('bootstrap')   {{-- only when the active profile includes Bootstrap --}}
```

Requesting an entry the profile did not install throws a render error. For
example, `@asset_css('bootstrap')` under `tailwind` fails with guidance to
install the `both` profile if both frameworks are intended.

---

## 4. Layouts, navigation, components and page assets

The view engine already has everything needed:

- `@extends` for layouts;
- `@section` / `@yield` / `@parent` for slots and defaults;
- `@include` for shared navigation and reusable components;
- escaped `{{ }}` output for attributes and values.

The sample base layout (`views/layouts/app.sahil.php`) does this:

```php
<head>
    <meta name="generator" content="SahilPHP">
    <title>@yield('title', app_name ?? 'SahilPHP')</title>
    @asset_client
    @asset_css('main')
    @yield('head')
</head>
<body class="@yield('body_class')">
    @yield('nav')

    <main class="@yield('main_class')">
        @yield('content')
    </main>

    @asset_js('main')
    @yield('scripts')
</body>
```

A page adds page-specific assets through sections:

```php
@extends('layouts.tailwind')

@section('head')
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('js/ui-kit.js') }}" defer></script>
@endsection
```

Shared components accept framework-specific class names as data, so one
partial works with every profile:

```php
@include('partials.field', [
    'id' => 'email',
    'name' => 'email',
    'type' => 'email',
    'label' => 'Email',
    'value' => form.email,
    'error' => errors.email,
    'input_class' => 'form-control',
    'error_class' => 'invalid-feedback d-block',
])
```

Available examples:

| File | Purpose |
|---|---|
| `views/layouts/app.sahil.php` | Shared base layout and asset slots |
| `views/layouts/tailwind.sahil.php` | Tailwind sample shell |
| `views/layouts/bootstrap.sahil.php` | Bootstrap sample shell |
| `views/layouts/plain.sahil.php` | Plain CSS sample shell |
| `views/partials/nav.sahil.php` | Shared navigation |
| `views/partials/field.sahil.php` | Labelled input and validation error |
| `views/partials/select.sahil.php` | Labelled select |
| `views/partials/alert.sahil.php` | Alert component |
| `views/partials/table.sahil.php` | Responsive table |
| `views/examples/tailwind.sahil.php` | Forms, errors, alerts, table, responsive grid |
| `views/examples/bootstrap.sahil.php` | Same examples with Bootstrap classes |
| `views/examples/plain.sahil.php` | Same examples with the plain CSS components |

`GET /ui-kit` renders the page that matches the active profile
(`App\Http\UiKitController`). In development the page points at the Vite
server; after `ui:build` it points at the built files.

---

## 5. Development and production

Development (`APP_ENV` not `production`, or `ASSET_MODE=development`):

```html
<script type="module" src="http://127.0.0.1:5173/@vite/client"></script>
<link rel="stylesheet" href="http://127.0.0.1:5173/resources/css/tailwind.css">
```

Production (`APP_ENV=production`, or `ASSET_MODE=production`):

```html
<link rel="stylesheet" href="/build/assets/tailwind.css">
```

Bootstrap adds its module script:

```html
<link rel="stylesheet" href="/build/assets/bootstrap.css">
<script type="module" src="/build/assets/bootstrap.js"></script>
```

Typical workflow:

```bash
php sahil ui:install tailwind
php sahil ui:theme        # optional neutral theme tokens
php sahil ui:dev          # during development
php sahil ui:build        # before production
php sahil ui:status       # verify sources, packages and outputs
```

A missing production build is a render error by default. Set
`ASSET_VERIFY=false` only when another system guarantees the files are
available.

---

## 6. Security notes

- Asset URLs are generated from a closed entry-point map, never from a
  template-provided URL.
- `asset()` rejects traversal, absolute URLs and query strings before
  percent-encoding the path.
- Attribute values and generated URLs are HTML-escaped with
  `Sahil\View\Escaper`.
- Event-handler attributes and managed `href`/`src`/`rel`/`type` attributes
  are rejected.
- Template output remains escaped by default; asset directives are the only
  new raw channel, and they emit only tags produced by the manager.

---

## 7. Tests

- `tests/Assets/AssetManagerTest.php` — profile entry maps, development and
  production URLs, attribute escaping, traversal rejection and missing-build
  errors.
- `tests/View/AssetRenderingTest.php` — rendering `@asset_*`, `asset()`,
  layout sections and page-specific assets.
- `tests/View/SampleViewsTest.php` — every sample page against its profile,
  including checks that the other framework's files are not emitted.
- `tests/View/AssetProviderTest.php` — the view engine receives the manager
  from the container.
- `tests/Http/UiKitRouteTest.php` — `/ui-kit` selects the Bootstrap page and
  its bootstrap-only asset tags.
