@props(['label' => null, 'as' => 'a', 'size' => 'base'])

{{-- Adding sits where the new item will show up: at the end of a list read in
     flow order, at the top of a reverse-chronological one. Filled rather than
     outlined, so it reads as an action and not as a placeholder; the hover only
     deepens that fill, it does not flip to the full accent. The small size is
     for adding inside a row rather than to the list. --}}
@php
    $sizes = [
        'base' => 'gap-2 py-4 text-sm',
        'sm' => 'gap-1.5 px-3 py-1.5 text-xs',
    ];

    $classes = implode(' ', [
        'flex items-center justify-center rounded-xl border border-dashed border-accent/40 bg-accent-soft font-bold text-accent transition hover:bg-accent/20',
        $sizes[$size] ?? $sizes['base'],
    ]);

    $icon = $size === 'sm' ? 'size-4' : 'size-5';
@endphp

@if ($as === 'button')
    <button {{ $attributes->class($classes) }}>
        <x-heroicon-o-plus @class([$icon]) />
        {{ $label ?? $slot }}
    </button>
@else
    <a {{ $attributes->class($classes) }}>
        <x-heroicon-o-plus @class([$icon]) />
        {{ $label ?? $slot }}
    </a>
@endif
