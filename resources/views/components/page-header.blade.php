@props(['title' => null, 'back' => null, 'eyebrow' => null, 'subtitle' => null])

<div {{ $attributes->class('mb-8 flex flex-wrap items-end justify-between gap-x-8 gap-y-4 sm:mb-9') }}>
    <div class="flex min-w-0 flex-1 items-center gap-3">
        @if ($back)
            <x-button :href="$back" as="a" variant="ghost" size="icon" class="-ml-3" wire:navigate aria-label="{{ __('Back') }}">
                <x-heroicon-o-chevron-left class="size-5" />
            </x-button>
        @endif

        <div class="min-w-0 flex-1">
            @if ($eyebrow)
                <p class="mb-1.5 text-[11.5px] font-bold tracking-[0.09em] text-ink-muted uppercase sm:text-[12.5px]">{{ $eyebrow }}</p>
            @endif

            {{-- The tight leading crops descenders once `truncate` hides the overflow,
                 so the box gets a little room under the baseline. --}}
            <h1 class="truncate pb-1 text-2xl leading-[1.1] font-semibold tracking-[-0.03em] text-title sm:text-[32px]">
                {{ $title ?? $slot }}
            </h1>

            @if ($subtitle)
                <p class="mt-1.5 text-sm font-medium text-pretty text-ink-soft sm:text-[15.5px]">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    {{ $actions ?? '' }}
</div>
