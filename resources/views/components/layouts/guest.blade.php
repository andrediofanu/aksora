<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@isset($title){{ $title }} — @endif{{ config('app.name', 'LMS') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 font-sans text-slate-800">
        <div class="w-full max-w-xl">
            {{ $slot }}

            <p class="mt-8 text-center text-xs text-slate-400">
                © {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>
    </body>
</html>
