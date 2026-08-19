<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

        @include('layouts.partials.theme-script')

        {{-- Preloads and @font-face for the self-hosted family declared in vite.config.js. --}}
        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles

        @laravelPWA
    </head>
    <body class="flex min-h-full flex-col bg-canvas text-ink antialiased">
        <div class="flex flex-1 flex-col justify-center px-5 py-12">
            <div class="mx-auto w-full max-w-sm rounded-3xl border border-line bg-surface p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
    </body>
</html>
