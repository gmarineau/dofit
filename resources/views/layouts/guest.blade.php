<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="flex h-full flex-col bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-10">
            <a href="{{ route('login') }}" class="mb-6 text-2xl font-bold text-brand-700 dark:text-brand-300" wire:navigate>
                {{ config('app.name') }}
            </a>

            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>
        </div>

        <p class="pb-6 text-center text-xs text-zinc-400">
            {{ config('app.name') }} — {{ date('Y') }}
        </p>

        @livewireScripts
    </body>
</html>
