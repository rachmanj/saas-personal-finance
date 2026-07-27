<!DOCTYPE html>
<html data-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Personal Finance</title>
    @viteReactRefresh
    @vite('resources/js/app.jsx')
</head>
<body style="margin:0; background:#141414;">
    @inertia
</body>
</html>
