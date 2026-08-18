@props(['value' => null])

<label {{ $attributes->class('mb-2 block text-sm font-bold text-ink-soft') }}>
    {{ $value ?? $slot }}
</label>
