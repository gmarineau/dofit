@props(['label' => null, 'for' => null, 'error' => null])

<div {{ $attributes->class('mb-5') }}>
    @if ($label)
        <x-label :for="$for" :value="$label" />
    @endif

    {{ $slot }}

    <x-error :messages="$error" />
</div>
