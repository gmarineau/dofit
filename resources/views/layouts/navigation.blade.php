@php
    $links = [
        ['route' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'o-home'],
        ['route' => 'trainings.index', 'label' => __('Trainings'), 'icon' => 'o-bolt'],
        ['route' => 'programs.index', 'label' => __('Programs'), 'icon' => 'o-rectangle-stack'],
        ['route' => 'metrics.index', 'label' => __('Metrics'), 'icon' => 'o-scale'],
    ];
@endphp

{{-- Desktop: a quiet bar along the top. --}}
<header class="sticky top-0 z-30 hidden bg-canvas sm:block">
    <div class="mx-auto flex h-20 w-full max-w-2xl items-center gap-1 px-4">
        <a href="{{ route('dashboard') }}" wire:navigate class="mr-3 text-xl font-extrabold tracking-tight text-ink">
            {{ config('app.name') }}
        </a>

        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                wire:navigate
                @class([
                    'rounded-full px-3 py-1.5 text-sm font-bold whitespace-nowrap transition',
                    'bg-accent-soft text-accent' => request()->routeIs($link['route']),
                    'text-ink-soft hover:text-ink' => ! request()->routeIs($link['route']),
                ])
            >
                {{ $link['label'] }}
            </a>
        @endforeach

        <div class="ml-auto flex items-center gap-1">
            <x-theme-toggle />

            <a
                href="{{ route('account') }}"
                wire:navigate
                @class([
                    'inline-flex size-11 items-center justify-center rounded-full transition',
                    'bg-accent-soft text-accent' => request()->routeIs('account*'),
                    'text-ink-soft hover:bg-raised hover:text-ink' => ! request()->routeIs('account*'),
                ])
                aria-label="{{ __('Account') }}"
            >
                <x-heroicon-o-user-circle class="size-5" />
            </a>
        </div>
    </div>
</header>

{{-- Mobile: a tab bar within thumb reach. --}}
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-canvas pb-[env(safe-area-inset-bottom)] sm:hidden">
    <div class="flex items-stretch">
        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                wire:navigate
                @class([
                    'flex flex-1 flex-col items-center gap-1 py-2.5 text-[10px] font-bold transition',
                    'text-accent' => request()->routeIs($link['route']),
                    'text-ink-muted' => ! request()->routeIs($link['route']),
                ])
            >
                <x-dynamic-component :component="'heroicon-'.$link['icon']" class="size-6" />
                <span class="max-w-full truncate px-0.5">{{ $link['label'] }}</span>
            </a>
        @endforeach

        <a
            href="{{ route('account') }}"
            wire:navigate
            @class([
                'flex flex-1 flex-col items-center gap-1 py-2.5 text-[10px] font-bold transition',
                'text-accent' => request()->routeIs('account*'),
                'text-ink-muted' => ! request()->routeIs('account*'),
            ])
        >
            <x-heroicon-o-user-circle class="size-6" />
            <span class="max-w-full truncate px-0.5">{{ __('Account') }}</span>
        </a>
    </div>
</nav>
