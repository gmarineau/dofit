@props([
    /** @var list<string> */
    'labels' => [],
    /** @var list<array{label: string, data: list<int|float>}> */
    'datasets' => [],
    'height' => 'h-52',
])

@if ($labels === [] || $datasets === [])
    <p {{ $attributes->class('py-10 text-center text-sm font-semibold text-ink-muted') }}>
        {{ __('Not enough data to draw a chart yet.') }}
    </p>
@else
    {{-- wire:ignore keeps Livewire from wiping the canvas Chart.js owns. --}}
    <div
        wire:ignore
        x-data="lineChart(@js($labels), @js($datasets))"
        {{ $attributes->class('relative '.$height) }}
    >
        <canvas x-ref="canvas"></canvas>
    </div>
@endif
