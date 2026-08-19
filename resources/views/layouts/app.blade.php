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
    @php
        // Every page shares one container width. The dashboard brings its own
        // cards, so it opts out of the single surface the others sit on.
        $card ??= true;
    @endphp
    <body class="min-h-full bg-canvas text-ink antialiased">
        @include('layouts.navigation')

        <main class="mx-auto w-full max-w-[73.75rem] px-4 pt-6 pb-[calc(6rem+env(safe-area-inset-bottom))] sm:px-8 sm:pt-9 sm:pb-16">
            @if ($card)
                <div class="rounded-card border border-line bg-surface p-5 sm:p-8">
                    {{ $slot }}
                </div>
            @else
                {{ $slot }}
            @endif
        </main>

        @livewireScripts
    </body>
</html>
