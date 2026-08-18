@props(['icon' => 'o-inbox'])

<div {{ $attributes->class('flex flex-col items-center py-14 text-center') }}>
    <div class="flex size-14 items-center justify-center rounded-full bg-accent-soft">
        <x-dynamic-component :component="'heroicon-'.$icon" class="size-7 text-accent" />
    </div>

    <p class="mt-4 max-w-xs font-semibold text-ink-soft">{{ $slot }}</p>

    @if (isset($action))
        <div class="mt-6">{{ $action }}</div>
    @endif
</div>
