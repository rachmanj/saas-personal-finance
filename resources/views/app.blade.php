<!DOCTYPE html>
<html data-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Personal Finance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,400;8..144,500;8..144,600;8..144,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --m3-surface: #1c1b1f;
            --m3-surface-container: #232227;
            --m3-surface-container-high: #2b292e;
        }
        html[data-theme='dark'] body { background: var(--m3-surface); }
        html[data-theme='light'] body { background: #fef7ff; }
    </style>
    @viteReactRefresh
    @vite('resources/js/app.jsx')
</head>
<body style="margin:0;">
    @inertia
</body>
</html>
