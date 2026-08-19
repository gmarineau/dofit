@props(['label' => null, 'value' => null, 'unit' => null, 'caption' => null, 'icon' => null, 'tone' => 'neutral'])

<div {{ $attributes->class('flex flex-col gap-3 rounded-2xl border border-line bg-surface px-4 py-4 sm:px-5 sm:py-5') }}>
    <div class="flex items-center gap-1.5 text-[10.5px] font-bold tracking-[0.09em] text-ink-muted uppercase sm:text-[11.5px]">
        @if ($icon)
            <x-dynamic-component :component="'heroicon-'.$icon" class="size-3.5 shrink-0" stroke-width="1.7" />
        @endif

        {{ $label }}
    </div>

    <div class="flex items-baseline gap-[5px]">
        <span class="numeric text-2xl leading-none font-extrabold tracking-[-0.03em] text-ink sm:text-[32px]">{{ $value }}</span>

        @if ($unit)
            <span class="text-sm font-bold text-ink-muted">{{ $unit }}</span>
        @endif
    </div>

    @if ($caption)
        {{-- A movement the user earned reads warm; a plain period reads quiet. --}}
        <div @class([
            'text-xs font-semibold sm:text-[13px]',
            'text-warm' => $tone === 'warm',
            'text-ink-soft' => $tone !== 'warm',
        ])>{{ $caption }}</div>
    @endif
</div>
