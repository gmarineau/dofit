<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

        @include('layouts.partials.theme-script')

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles

        @laravelPWA
    </head>
    <body class="min-h-full bg-canvas text-ink antialiased">
        @include('layouts.navigation')

        <main class="mx-auto w-full max-w-2xl px-4 pt-4 pb-[calc(8rem+env(safe-area-inset-bottom))] sm:pb-10">
            <div class="rounded-3xl border border-line bg-surface p-5 sm:p-8">
                {{ $slot }}
            </div>
        </main>

        @livewireScripts
    </body>
</html>
