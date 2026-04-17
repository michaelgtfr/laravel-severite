<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    {{-- Pointe vers les assets publiés de severite --}}
    @vite(['resources/js/app.ts'], 'build/severite')

    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>