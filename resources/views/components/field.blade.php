@props(['label' => null, 'for' => null, 'error' => null])

<div {{ $attributes->class('mb-4') }}>
    @if ($label)
        <x-label :for="$for" :value="$label" />
    @endif

    {{ $slot }}

    <x-error :messages="$error" />
</div>
