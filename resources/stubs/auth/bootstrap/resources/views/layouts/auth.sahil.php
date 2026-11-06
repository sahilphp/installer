<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', app_name ?? 'SahilPHP')</title>
    <link rel="stylesheet" href="/css/bootstrap.css">
</head>
<body>
    <div class="container py-5">
        <div class="text-center mb-4">
            <h2 class="fw-semibold">
                <a href="/">SahilPHP</a>
            </h2>
        </div>

        <div class="card">
            <div class="card-body">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
