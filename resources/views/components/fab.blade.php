@props(['label' => null])

{{-- Thumb-reachable primary action, phones only; desktop uses the header button. --}}
<a
    {{ $attributes->class('fixed right-5 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-30 flex size-14 items-center justify-center rounded-full bg-accent text-accent-ink shadow-lg shadow-accent/30 transition active:scale-95 sm:hidden') }}
    aria-label="{{ $label }}"
>
    <x-heroicon-o-plus class="size-6" />
</a>
