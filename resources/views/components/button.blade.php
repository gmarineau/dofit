@props([
    'variant' => 'primary',
    'size' => 'base',
    'as' => 'button',
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-accent text-accent-ink hover:brightness-110',
        'secondary' => 'border border-line bg-surface text-ink hover:bg-raised',
        'ghost' => 'text-ink-soft hover:bg-raised hover:text-ink',
        'danger' => 'bg-danger text-white hover:brightness-110',
        'quiet-danger' => 'text-danger hover:bg-danger-soft',
    ];

    $sizes = [
        'base' => 'h-11 gap-2 rounded-full px-5 text-sm',
        'sm' => 'h-9 gap-1.5 rounded-full px-4 text-xs',
        'icon' => 'size-11 rounded-full',
        'icon-sm' => 'size-9 rounded-full',
    ];

    $classes = implode(' ', [
        'inline-flex shrink-0 items-center justify-center font-bold transition',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
        'disabled:pointer-events-none disabled:opacity-50',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['base'],
    ]);
@endphp

@if ($as === 'a')
    <a {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button @disabled($disabled) {{ $attributes->merge(['type' => 'submit'])->class($classes) }}>{{ $slot }}</button>
@endif
