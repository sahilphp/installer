<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', app_name ?? 'SahilPHP')</title>
    @asset_css('@fiberstyles')
    @asset_css('main')
</head>
<body>
    <div id="app">
        @yield('content')
    </div>

    @asset_js('@fiberscripts')
    @asset_js('main')
    @asset_js('kit')
</body>
</html>
