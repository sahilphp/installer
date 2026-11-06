<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', app_name ?? 'SahilPHP')</title>
    <link rel="stylesheet" href="/css/tailwind.css">
</head>
<body class="min-h-screen bg-slate-50">
    <div class="flex min-h-screen flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="text-center text-3xl font-bold tracking-tight">
                <a href="/">SahilPHP</a>
            </h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white px-4 py-8 shadow sm:rounded-lg sm:px-10">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
