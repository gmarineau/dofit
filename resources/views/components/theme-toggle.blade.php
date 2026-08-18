<button
    type="button"
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = ! this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    }"
    x-on:click="toggle()"
    {{ $attributes->class('inline-flex size-11 shrink-0 items-center justify-center rounded-full text-ink-soft transition hover:bg-raised hover:text-ink') }}
    :aria-label="dark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
>
    <x-heroicon-o-sun class="size-5" x-show="dark" x-cloak />
    <x-heroicon-o-moon class="size-5" x-show="! dark" x-cloak />
</button>
