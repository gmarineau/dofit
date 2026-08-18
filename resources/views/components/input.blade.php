@props(['disabled' => false, 'invalid' => false])

<input
    @disabled($disabled)
    {{ $attributes->class([
        'block w-full rounded-lg border-0 px-3 py-2 text-base shadow-sm ring-1 ring-inset transition',
        'bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100',
        'focus:ring-2 focus:ring-inset focus:outline-none',
        'disabled:cursor-not-allowed disabled:opacity-60',
        'ring-danger focus:ring-danger' => $invalid,
        'ring-zinc-300 focus:ring-brand-600 dark:ring-zinc-700' => ! $invalid,
    ]) }}
>
