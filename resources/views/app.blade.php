<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PostVisit.ai</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-white text-gray-900">
    <div id="app"></div>
    <script>window.__APP_CONFIG__={demoLoginEnabled:@json(config('app.demo_login_enabled'))};</script>
</body>
</html>
