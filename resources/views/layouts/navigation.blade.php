@php
    $links = [
        ['route' => 'dashboard', 'label' => __('Trainings'), 'icon' => 'dumbbell'],
        ['route' => 'metrics.index', 'label' => __('Metrics'), 'icon' => 'scale'],
        ['route' => 'activities.index', 'label' => __('Activities'), 'icon' => 'chart-line'],
        ['route' => 'reports.index', 'label' => __('Reports'), 'icon' => 'clipboard'],
        ['route' => 'activity-types.index', 'label' => __('Activity Types'), 'icon' => 'list'],
    ];
@endphp

<nav x-data="{ open: false }" class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto w-full max-w-3xl px-4">
        <div class="flex h-14 items-center justify-between">
            <a href="{{ route('dashboard') }}" class="text-lg font-bold text-brand-700 dark:text-brand-300" wire:navigate>
                {{ config('app.name') }}
            </a>

            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($links as $link)
                    <a
                        href="{{ route($link['route']) }}"
                        wire:navigate
                        @class([
                            'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium transition',
                            'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' => request()->routeIs($link['route']),
                            'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100' => ! request()->routeIs($link['route']),
                        ])
                    >
                        <x-dynamic-component :component="'icons.'.$link['icon']" class="size-4" />
                        <span class="hidden lg:inline">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-1">
                <div class="relative hidden sm:block" x-data="{ open: false }" x-on:click.outside="open = false">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        x-on:click="open = ! open"
                    >
                        <x-icons.user class="size-4" />
                        {{ auth()->user()->name }}
                    </button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition
                        class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-lg bg-white py-1 shadow-lg ring-1 ring-zinc-900/5 dark:bg-zinc-800 dark:ring-white/10"
                    >
                        <a href="{{ route('account') }}" wire:navigate class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                            {{ __('Account') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                                {{ __('Log out') }}
                            </button>
                        </form>
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 sm:hidden dark:text-zinc-400 dark:hover:bg-zinc-800"
                    x-on:click="open = ! open"
                    aria-label="{{ __('Toggle navigation') }}"
                >
                    <x-icons.menu />
                </button>
            </div>
        </div>
    </div>

    <div x-show="open" x-cloak class="border-t border-zinc-200 sm:hidden dark:border-zinc-800">
        <div class="space-y-1 px-4 py-3">
            @foreach ($links as $link)
                <a
                    href="{{ route($link['route']) }}"
                    wire:navigate
                    x-on:click="open = false"
                    @class([
                        'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium',
                        'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' => request()->routeIs($link['route']),
                        'text-zinc-600 dark:text-zinc-400' => ! request()->routeIs($link['route']),
                    ])
                >
                    <x-dynamic-component :component="'icons.'.$link['icon']" class="size-4" />
                    {{ $link['label'] }}
                </a>
            @endforeach

            <div class="mt-2 border-t border-zinc-200 pt-2 dark:border-zinc-800">
                <a href="{{ route('account') }}" wire:navigate x-on:click="open = false" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    <x-icons.user class="size-4" />
                    {{ auth()->user()->name }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-zinc-600 dark:text-zinc-400">
                        {{ __('Log out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
