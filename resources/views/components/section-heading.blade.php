@props(['icon' => null])

<h2 {{ $attributes->class('mb-1 flex items-center gap-1.5 text-xs font-extrabold tracking-widest text-ink-muted uppercase') }}>
    @if ($icon)
        <x-dynamic-component :component="'heroicon-'.$icon" class="size-3.5" />
    @endif

    {{ $slot }}
</h2>
