{{--
    Dashboard layout (Bootstrap 5).

    A two-column shell: sidebar navigation card, top navbar with the official
    logo, and a bordered content area for the page.
--}}
<!doctype html>
<html lang="{{ lang ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generator" content="SahilPHP">
    <meta name="theme-color" content="#0b6fe8">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>@yield('title', app_name ?? 'SahilPHP')</title>

    @asset_client
    @asset_css('main')
    @asset_css('@fiberstyles')

    @yield('head')
</head>
<body class="bg-body-tertiary">
    <header class="border-bottom bg-white sticky-top">
        <nav class="navbar container py-2" aria-label="Dashboard navigation">
            @include('partials.brand', ['home_url' => '/dashboard'])

            <div class="d-flex align-items-center gap-3">
                <span class="badge text-bg-light fw-medium d-none d-sm-inline">Demo data</span>

                <span class="rounded-circle bg-primary-subtle text-primary fw-semibold d-inline-flex align-items-center justify-content-center"
                    style="width: 2rem; height: 2rem; font-size: .75rem;">
                    {{ user.initials ?? 'AL' }}
                </span>

                <span class="d-none d-sm-block lh-sm small">
                    <span class="d-block fw-medium">{{ user.name ?? 'Signed-in user' }}</span>
                    <span class="d-block text-muted" style="font-size: .75rem;">{{ user.email ?? '' }}</span>
                </span>
            </div>
        </nav>
    </header>

    <div class="container py-4">
        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-2">
                        <div class="list-group list-group-flush">
                            <a class="list-group-item list-group-item-action active rounded-3 mb-1" href="/dashboard" aria-current="page">Overview</a>
                            <a class="list-group-item list-group-item-action rounded-3 mb-1" href="/ui-kit">Components</a>
                            <a class="list-group-item list-group-item-action rounded-3 mb-1" href="/health">Health</a>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mt-3">
                    <div class="card-body p-3 small">
                        <p class="fw-semibold mb-1">{{ user.name ?? 'Signed-in user' }}</p>
                        <p class="text-muted mb-2">{{ user.role ?? 'Member' }}</p>
                        <a class="text-decoration-none" href="/">← Back to site</a>
                    </div>
                </div>
            </aside>

            <div class="col-lg-9">
                @yield('content')
            </div>
        </div>
    </div>

    @asset_js('main')
    @asset_js('@fiberscripts')
    @yield('scripts')
</body>
</html>
