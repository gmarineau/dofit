@props(['title' => null, 'back' => null])

<div {{ $attributes->class('flex items-center gap-3') }}>
    @if ($back)
        <x-button :href="$back" as="a" variant="secondary" size="icon" wire:navigate aria-label="{{ __('Back') }}">
            <x-icons.chevron-left />
        </x-button>
    @endif

    <h1 class="min-w-0 flex-1 truncate text-xl font-semibold text-zinc-900 dark:text-zinc-100">
        {{ $title ?? $slot }}
    </h1>

    {{ $actions ?? '' }}
</div>
