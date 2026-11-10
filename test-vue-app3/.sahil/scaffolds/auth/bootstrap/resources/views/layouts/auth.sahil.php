{{--
    Authentication layout (Bootstrap 5).

    Centered card shell for the login, register and password pages, with the
    official SahilPHP lockup above the card.
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
    <div class="min-vh-100 d-flex flex-column align-items-center justify-content-center px-3 py-5">
        <a class="mb-4 text-decoration-none" href="/" aria-label="SahilPHP home">
            <img class="brand__logo brand__logo--lg" src="{{ asset('logo.png') }}" alt="SahilPHP"
                width="380" height="160" style="height: 2.75rem; width: auto;" decoding="async">
        </a>

        <div class="card border-0 shadow-sm rounded-4 w-100" style="max-width: 26rem;">
            <div class="card-body p-4 p-sm-5">
                @yield('content')
            </div>
        </div>

        <p class="mt-4 mb-0 text-muted" style="font-size: .75rem;">
            © {{ year ?? '' }} {{ app_name ?? 'SahilPHP' }} · powered by SahilPHP
        </p>
    </div>

    @asset_js('main')
    @asset_js('@fiberscripts')
    @yield('scripts')
</body>
</html>
