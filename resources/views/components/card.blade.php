@props(['header' => null])

<div {{ $attributes->class('overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5 dark:bg-zinc-900 dark:ring-white/10') }}>
    @if ($header)
        <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-800 dark:text-zinc-200">
            {{ $header }}
        </div>
    @endif

    {{ $slot }}
</div>
