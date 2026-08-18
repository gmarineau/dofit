@props(['disabled' => false, 'invalid' => false])

<input
    @disabled($disabled)
    {{ $attributes->class([
        'block h-12 w-full rounded-xl border bg-raised px-3.5 font-semibold text-ink transition',
        'placeholder:font-normal placeholder:text-ink-muted',
        'focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none',
        'disabled:cursor-not-allowed disabled:opacity-50',
        'border-danger' => $invalid,
        'border-line' => ! $invalid,
    ]) }}
>
