@props([
    'variant' => 'primary',
    'size' => 'base',
    'as' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-brand-700 text-white hover:bg-brand-800 focus-visible:outline-brand-700 dark:bg-brand-600 dark:hover:bg-brand-500',
        'secondary' => 'bg-white text-zinc-700 ring-1 ring-zinc-300 ring-inset hover:bg-zinc-50 focus-visible:outline-zinc-400 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700 dark:hover:bg-zinc-700',
        'success' => 'bg-success text-zinc-900 hover:brightness-95 focus-visible:outline-success',
        'danger' => 'bg-danger text-white hover:brightness-95 focus-visible:outline-danger',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-zinc-400 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
    ];

    $sizes = [
        'base' => 'gap-2 px-4 py-2 text-sm',
        'sm' => 'gap-1.5 px-2.5 py-1.5 text-xs',
        'icon' => 'p-2',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-lg font-medium transition',
        'focus-visible:outline-2 focus-visible:outline-offset-2',
        'disabled:pointer-events-none disabled:opacity-60',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['base'],
    ]);
@endphp

@if ($as === 'a')
    <a {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($classes) }}>{{ $slot }}</button>
@endif
