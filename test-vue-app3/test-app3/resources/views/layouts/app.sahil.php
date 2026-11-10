{{-- Bootstrap 5 layout. --}}
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

    {{-- Vite HMR client (development only) and the active profile's CSS. --}}
    @asset_client
    @asset_css('main')
    @asset_css('@fiberstyles')

    {{-- Page-specific styles: define a `head` section in the page. --}}
    @yield('head')
</head>
<body class="@yield('body_class', 'bg-light')">
    @section('nav')
        @include('partials.nav', [
            'links' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => 'UI kit', 'href' => '/ui-kit'],
                ['label' => 'Health', 'href' => '/health'],
            ],
        ])
    @endsection

    @yield('nav')

    <main class="@yield('main_class', 'container py-5')">
        @yield('content')
    </main>

    @section('footer')
        <footer class="border-top bg-white py-4 mt-5">
            <div class="container d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    @include('partials.brand', ['variant' => 'mark'])
                    <span class="text-muted small">Built with SahilPHP.</span>
                </div>

                <nav class="d-flex gap-4 small" aria-label="Footer navigation">
                    <a class="link-secondary text-decoration-none" href="/">Home</a>
                    <a class="link-secondary text-decoration-none" href="/ui-kit">UI kit</a>
                    <a class="link-secondary text-decoration-none" href="/health">Health</a>
                </nav>
            </div>
        </footer>
    @endsection

    @yield('footer')

    {{-- Profile JavaScript, then page-specific scripts. --}}
    @asset_js('main')
    @asset_js('kit')
    @asset_js('@fiberscripts')
    @yield('scripts')
</body>
</html>
