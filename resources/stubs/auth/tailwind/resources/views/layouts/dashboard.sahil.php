<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', app_name ?? 'SahilPHP')</title>
    <link rel="stylesheet" href="/css/tailwind.css">
</head>
<body class="min-h-screen bg-slate-50">
    <header class="border-b border-slate-200">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="/" class="text-lg font-semibold text-slate-900">SahilPHP</a>
            <span class="text-sm text-slate-500">Welcome back, <span class="font-medium text-slate-700">{{ user.name }}</span></span>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @yield('content')
    </main>
</body>
</html>
