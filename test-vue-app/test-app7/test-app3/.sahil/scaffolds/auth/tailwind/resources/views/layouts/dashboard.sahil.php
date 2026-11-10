{{--
    Dashboard layout (Tailwind CSS v4).

    A sidebar shell in the style of shadcn/ui dashboards: muted page
    background, bordered surfaces, compact type and the official logo in the
    sidebar header.
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
    <div class="flex min-h-screen">
        <aside class="hidden w-60 shrink-0 flex-col border-r border-slate-200 bg-white lg:flex">
            <div class="flex h-14 items-center border-b border-slate-200 px-4">
                @include('partials.brand', ['home_url' => '/dashboard'])
            </div>

            <nav class="flex-1 space-y-1 p-3 text-sm" aria-label="Dashboard">
                <a class="flex items-center gap-2.5 rounded-md bg-slate-100 px-3 py-2 font-medium text-slate-900"
                    href="/dashboard" aria-current="page">Overview</a>
                <a class="flex items-center gap-2.5 rounded-md px-3 py-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    href="/ui-kit">Components</a>
                <a class="flex items-center gap-2.5 rounded-md px-3 py-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    href="/health">Health</a>
            </nav>

            <div class="border-t border-slate-200 p-3 text-xs text-slate-500">
                <a class="transition hover:text-slate-900" href="/">← Back to site</a>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-10 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur">
                <span class="lg:hidden">@include('partials.brand', ['home_url' => '/dashboard']) </span>

                <div class="ml-auto flex items-center gap-3">
                    <span class="hidden rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-500 sm:inline">Demo data</span>

                    <span class="grid h-8 w-8 place-items-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                        {{ user.initials ?? 'AL' }}
                    </span>

                    <span class="hidden text-sm leading-tight sm:block">
                        <span class="block font-medium text-slate-900">{{ user.name ?? 'Signed-in user' }}</span>
                        <span class="block text-xs text-slate-500">{{ user.email ?? '' }}</span>
                    </span>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    @asset_js('main')
    @asset_js('@fiberscripts')
    @yield('scripts')
</body>
</html>
