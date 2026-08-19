@props(['size' => 'base'])

{{-- The user's initials stand in for a photo: the account has none, and a
     wordmark-only bar needed something to anchor its right edge. --}}
@php
    $sizes = [
        'base' => 'size-[34px] text-[13px]',
        'sm' => 'size-8 text-xs',
    ];

    $classes = implode(' ', [
        'inline-flex shrink-0 items-center justify-center rounded-full bg-accent-soft font-extrabold text-accent transition',
        'hover:brightness-95',
        request()->routeIs('account*') ? 'ring-2 ring-accent' : '',
        $sizes[$size] ?? $sizes['base'],
    ]);
@endphp

<a {{ $attributes->class($classes) }} aria-label="{{ __('Account') }}">
    {{ auth()->user()->initials() }}
</a>
