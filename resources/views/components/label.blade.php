@props(['value' => null])

<label {{ $attributes->class('mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300') }}>
    {{ $value ?? $slot }}
</label>
