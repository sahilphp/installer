<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', app_name ?? 'SahilPHP')</title>
    <link rel="stylesheet" href="/css/bootstrap.css">
</head>
<body>
    <header class="navbar navbar-expand navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="/">SahilPHP</a>
            <span class="navbar-text text-white-50">Welcome back, <span class="fw-medium">{{ user.name }}</span></span>
        </div>
    </header>

    <main class="container py-4">
        @yield('content')
    </main>
</body>
</html>
