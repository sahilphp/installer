{{--
    Authentication layout (Tailwind CSS v4).

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
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a class="mb-8 inline-flex" href="/" aria-label="SahilPHP home">
            <img class="h-10 w-auto" src="{{ asset('logo.png') }}" alt="SahilPHP"
                width="380" height="160" decoding="async">
        </a>

        <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @yield('content')
        </div>

        <p class="mt-6 text-xs text-slate-400">
            © {{ year ?? '' }} {{ app_name ?? 'SahilPHP' }} · powered by SahilPHP
        </p>
    </div>

    @asset_js('main')
    @asset_js('@fiberscripts')
    @yield('scripts')
</body>
</html>
