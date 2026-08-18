@props([
    /** @var list<array{label: string, values: list<int|float>, color?: string}> */
    'series' => [],
    /** @var list<string> */
    'labels' => [],
    'height' => 180,
])

@php
    $width = 320;
    $padding = ['top' => 12, 'right' => 8, 'bottom' => 22, 'left' => 34];

    $series = array_values(array_filter($series, fn (array $line): bool => $line['values'] !== []));

    $allValues = array_merge(...array_map(fn (array $line): array => $line['values'], $series)) ?: [];

    $min = $allValues ? min($allValues) : 0;
    $max = $allValues ? max($allValues) : 0;

    // Pad a flat series so its line lands in the middle instead of on an edge.
    if ($min === $max) {
        $min -= 1;
        $max += 1;
    }

    $plotWidth = $width - $padding['left'] - $padding['right'];
    $plotHeight = $height - $padding['top'] - $padding['bottom'];

    $x = function (int $index, int $count) use ($padding, $plotWidth): float {
        return $count < 2
            ? $padding['left'] + $plotWidth / 2
            : $padding['left'] + $index * ($plotWidth / ($count - 1));
    };

    $y = fn (int|float $value): float => $padding['top'] + $plotHeight - (($value - $min) / ($max - $min)) * $plotHeight;

    $palette = ['#07689f', '#f67280', '#11d3bc'];

    // Show at most three x labels so they stay readable on a phone.
    $labelCount = count($labels);
    $labelIndexes = match (true) {
        $labelCount === 0 => [],
        $labelCount <= 2 => range(0, $labelCount - 1),
        default => [0, intdiv($labelCount - 1, 2), $labelCount - 1],
    };
@endphp

<div {{ $attributes->class('w-full') }}>
    @if ($series === [])
        <x-empty-state>{{ __('Not enough data to draw a chart yet.') }}</x-empty-state>
    @else
        <svg
            viewBox="0 0 {{ $width }} {{ $height }}"
            class="w-full"
            preserveAspectRatio="none"
            role="img"
            aria-label="{{ collect($series)->pluck('label')->join(', ') }}"
        >
            {{-- Horizontal guides at the bottom, middle and top of the value range. --}}
            @foreach ([$min, ($min + $max) / 2, $max] as $guide)
                <line
                    x1="{{ $padding['left'] }}"
                    y1="{{ round($y($guide), 2) }}"
                    x2="{{ $width - $padding['right'] }}"
                    y2="{{ round($y($guide), 2) }}"
                    class="stroke-zinc-200 dark:stroke-zinc-800"
                    stroke-width="1"
                    vector-effect="non-scaling-stroke"
                />
            @endforeach

            <text x="2" y="{{ round($y($max), 2) + 3 }}" class="fill-zinc-400 text-[9px]">{{ round($max, 1) }}</text>
            <text x="2" y="{{ round($y($min), 2) + 3 }}" class="fill-zinc-400 text-[9px]">{{ round($min, 1) }}</text>

            @foreach ($series as $index => $line)
                @php
                    $color = $line['color'] ?? $palette[$index % count($palette)];
                    $count = count($line['values']);
                    $points = collect($line['values'])
                        ->map(fn (int|float $value, int $point): string => round($x($point, $count), 2).','.round($y($value), 2))
                        ->join(' ');
                @endphp

                <polyline
                    points="{{ $points }}"
                    fill="none"
                    stroke="{{ $color }}"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    vector-effect="non-scaling-stroke"
                />

                @foreach ($line['values'] as $point => $value)
                    <circle
                        cx="{{ round($x($point, $count), 2) }}"
                        cy="{{ round($y($value), 2) }}"
                        r="2"
                        fill="{{ $color }}"
                        vector-effect="non-scaling-stroke"
                    />
                @endforeach
            @endforeach

            @foreach ($labelIndexes as $index)
                <text
                    x="{{ round($x($index, max($labelCount, 1)), 2) }}"
                    y="{{ $height - 6 }}"
                    text-anchor="{{ $index === 0 ? 'start' : ($index === $labelCount - 1 ? 'end' : 'middle') }}"
                    class="fill-zinc-400 text-[9px]"
                >{{ $labels[$index] }}</text>
            @endforeach
        </svg>

        <div class="mt-2 flex flex-wrap justify-center gap-4">
            @foreach ($series as $index => $line)
                <span class="inline-flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="size-2 rounded-full" style="background-color: {{ $line['color'] ?? $palette[$index % count($palette)] }}"></span>
                    {{ $line['label'] }}
                </span>
            @endforeach
        </div>
    @endif
</div>
