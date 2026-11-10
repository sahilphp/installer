# Views

Templates live here, are compiled to `storage/framework/views/`, and are
never executed directly. The language and its directives are documented in
[`docs/TEMPLATE_LANGUAGE.md`](../docs/TEMPLATE_LANGUAGE.md).

## Brand

`partials/brand.sahil.php` renders the official SahilPHP logo:

```php
@include('partials.brand')                                  # lockup: mark + wordmark
@include('partials.brand', ['variant' => 'mark'])           # mark only (footers)
```

Every layout loads the same favicon set (`favicon.ico`, `apple-touch-icon.png`)
and the welcome, auth and dashboard pages show the lockup, so a new project
looks finished before any code is written. The asset files are:

| File | Purpose |
|---|---|
| `public/favicon.ico` | Browser tab icon |
| `public/logo.png` | Official lockup used in navigation and heroes |
| `public/logo-mark.png` | Mark only, for compact surfaces |
| `public/apple-touch-icon.png` | iOS home-screen icon |

## Base set

- `layouts/app.sahil.php` — plain-profile layout: brand stylesheet, favicon
  set, navigation and footer;
- `layouts/auth.sahil.php`, `layouts/dashboard.sahil.php` — added by
  `php sahil unda:auth` (don't exist until you generate them);
- `partials/brand.sahil.php` — the logo links described above;
- `partials/nav.sahil.php` — shared navigation that renders the brand and
  accepts framework class names as data;
- `partials/field.sahil.php`, `partials/select.sahil.php`,
  `partials/alert.sahil.php`, `partials/table.sahil.php` — reusable
  components with escaped values;
- `pages/home.sahil.php` — the welcome page: logo hero, quick-start commands
  and feature cards;
- `examples/plain.sahil.php` — forms, validation errors, alerts, tables and
  navigation for the plain profile.

Installing a UI profile (`php sahil ui:install tailwind|bootstrap`) replaces
the layout, navigation, welcome, error and example templates with that
profile's versions. `GET /ui-kit` renders the example page that matches the
active profile.

## Authentication views

`php sahil unda:auth` adds these templates and connects their forms to the
generated server-side authentication routes:

- `auth/login.sahil.php`, `auth/register.sahil.php`,
  `auth/forgot-password.sahil.php`, `auth/reset-password.sahil.php`;
- `layouts/auth.sahil.php` — centered card shell;
- `layouts/dashboard.sahil.php` — sidebar shell;
- `pages/dashboard.sahil.php` — metrics, activity table and starter tasks.

Tailwind projects receive shadcn/ui-style pages; Bootstrap projects receive a
Bootstrap 5 design. Login, registration and password-reset forms include CSRF
tokens and render server-side validation feedback. The dashboard displays the
authenticated account and posts logout to `/logout`.

## Assets

Use the runtime helpers rather than hard-coded URLs:

```php
@asset_client
@asset_css('main')
@asset_js('main')
```

Page-specific files go in the layout's `head` and `scripts` sections:

```php
@extends('layouts.app')

@section('head')
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('js/ui-kit.js') }}" defer></script>
@endsection
```

See [`docs/VIEW_ASSETS.md`](../docs/VIEW_ASSETS.md) for profile selection,
configuration, escaping and the full helper reference.

The optional theme layer (`php sahil ui:theme`) changes the neutral
`--sahil-*` variables used by the plain layout and by generated framework
mappings. Application overrides belong in `resources/css/theme.overrides.css`;
see [`docs/THEMING.md`](../docs/THEMING.md).

Add template directories by adding them to `view.paths` in
`config/view.php`; the first directory that contains a template wins, so an
application can override a package's template by ordering its own directory
first.
