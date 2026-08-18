@props(['title' => null, 'back' => null, 'subtitle' => null])

<div {{ $attributes->class('mb-8 flex items-center gap-3') }}>
    @if ($back)
        <x-button :href="$back" as="a" variant="ghost" size="icon" class="-ml-3" wire:navigate aria-label="{{ __('Back') }}">
            <x-heroicon-o-chevron-left class="size-5" />
        </x-button>
    @endif

    <div class="min-w-0 flex-1">
        <h1 class="truncate text-3xl font-extrabold tracking-tight text-ink">{{ $title ?? $slot }}</h1>

        @if ($subtitle)
            <p class="mt-1 text-sm font-semibold text-ink-soft">{{ $subtitle }}</p>
        @endif
    </div>

    {{ $actions ?? '' }}
</div>
