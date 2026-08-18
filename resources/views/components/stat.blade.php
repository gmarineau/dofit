@props(['label' => null, 'value' => null, 'unit' => null, 'caption' => null])

<div {{ $attributes->class('rounded-2xl bg-raised px-4 py-3.5') }}>
    <div class="text-xs font-bold tracking-wide text-ink-muted uppercase">{{ $label }}</div>

    <div class="mt-1.5 flex items-baseline gap-1">
        <span class="numeric text-2xl leading-none font-extrabold text-ink">{{ $value }}</span>

        @if ($unit)
            <span class="text-sm font-semibold text-ink-muted">{{ $unit }}</span>
        @endif
    </div>

    @if ($caption)
        <div class="mt-1 text-xs font-semibold text-ink-soft">{{ $caption }}</div>
    @endif
</div>
