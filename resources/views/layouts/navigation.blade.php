@php
    $links = [
        ['route' => 'dashboard', 'label' => __('Dashboard'), 'short' => __('Home'), 'icon' => 'o-squares-2x2'],
        ['route' => 'trainings.index', 'label' => __('Trainings'), 'short' => __('Sessions'), 'icon' => 'o-bolt'],
        ['route' => 'programs.index', 'label' => __('Programs'), 'short' => __('Programs'), 'icon' => 'o-rectangle-stack'],
        ['route' => 'exercises.index', 'label' => __('Exercises'), 'short' => __('Exercises'), 'icon' => 'o-book-open'],
        ['route' => 'metrics.index', 'label' => __('Metrics'), 'short' => __('Metrics'), 'icon' => 'o-scale'],
    ];
@endphp

{{-- Desktop: a white bar over the paper canvas, aligned with the page below. --}}
<header class="sticky top-0 z-30 hidden border-b border-line bg-surface sm:block">
    <div class="mx-auto flex h-17 w-full max-w-[73.75rem] items-center gap-5 px-8">
        <a href="{{ route('dashboard') }}" wire:navigate class="shrink-0 text-[22px] font-extrabold tracking-[-0.04em] text-ink">
            {{ config('app.name') }}
        </a>

        <nav class="flex items-center gap-0.5">
            @foreach ($links as $link)
                <a
                    href="{{ route($link['route']) }}"
                    wire:navigate
                    @class([
                        'flex shrink-0 items-center gap-[7px] rounded-[9px] px-[11px] py-[9px] text-sm whitespace-nowrap transition',
                        'bg-accent-soft font-bold text-accent' => request()->routeIs($link['route']),
                        'font-semibold text-ink-soft hover:bg-raised hover:text-ink' => ! request()->routeIs($link['route']),
                    ])
                >
                    <x-dynamic-component :component="'heroicon-'.$link['icon']" class="size-[18px] shrink-0" stroke-width="1.7" />
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-1">
            <x-theme-toggle />

            <x-avatar :href="route('account')" />
        </div>
    </div>
</header>

{{-- Mobile: the wordmark is the way back to the dashboard, and the avatar
     carries the account, since the tab bar has no room for either. --}}
<header class="sticky top-0 z-30 flex h-14 items-center gap-2 border-b border-line bg-surface px-5 sm:hidden">
    <a href="{{ route('dashboard') }}" wire:navigate class="text-[19px] font-extrabold tracking-[-0.04em] text-ink">
        {{ config('app.name') }}
    </a>

    <div class="ml-auto flex shrink-0 items-center gap-1">
        <x-theme-toggle />

        <x-avatar :href="route('account')" size="sm" />
    </div>
</header>

{{-- Mobile: a tab bar within thumb reach. --}}
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface pb-[env(safe-area-inset-bottom)] sm:hidden">
    <div class="flex items-stretch px-2 pt-3 pb-2">
        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                wire:navigate
                @class([
                    'flex min-h-12 flex-1 flex-col items-center gap-[5px] text-[11px] font-bold transition',
                    'text-accent' => request()->routeIs($link['route']),
                    'text-ink-muted' => ! request()->routeIs($link['route']),
                ])
            >
                <x-dynamic-component :component="'heroicon-'.$link['icon']" class="size-6 shrink-0" stroke-width="1.7" />
                <span class="max-w-full truncate px-0.5">{{ $link['short'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
