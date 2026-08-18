<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ __('Offline') }} — {{ config('app.name') }}</title>

        @vite(['resources/css/app.css'])
    </head>
    <body class="flex h-full items-center justify-center bg-zinc-50 px-4 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="text-center">
            <p class="text-4xl font-bold text-brand-700 dark:text-brand-300">{{ config('app.name') }}</p>

            <h1 class="mt-6 text-lg font-semibold">{{ __('You are offline') }}</h1>

            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Check your connection and try again.') }}
            </p>

            <button
                type="button"
                onclick="window.location.reload()"
                class="mt-6 inline-flex items-center justify-center rounded-lg bg-brand-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-800"
            >
                {{ __('Retry') }}
            </button>
        </div>
    </body>
</html>
