@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null])

<div {{ $attributes->class('mb-5') }}>
    @if ($label)
        <x-label :for="$for" :value="$label" />
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p class="mt-2 flex items-center gap-1 text-xs font-semibold text-ink-muted">
            <x-heroicon-o-information-circle class="size-3.5 shrink-0" />
            {{ $hint }}
        </p>
    @endif

    <x-error :messages="$error" />
</div>
