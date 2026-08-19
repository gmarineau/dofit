@props([
    /** @var list<string> */
    'labels' => [],
    /** @var list<int|float> */
    'values' => [],
    'decimals' => 0,
])

@if ($labels === [] || $values === [] || max($values) <= 0)
    <p {{ $attributes->class('py-10 text-center text-sm font-semibold text-ink-muted') }}>
        {{ __('Not enough data to draw a chart yet.') }}
    </p>
@else
    {{-- Bars are drawn in CSS rather than on a canvas: the series is short, the
         shape is the whole message, and the last column has to stand out. The
         tallest bar stops at 86% so the value above it stays inside the frame. --}}
    @php
        $max = max($values);
        $last = array_key_last($values);
    @endphp

    <div {{ $attributes }}>
        <div class="flex h-33 items-end gap-2 border-b border-line-soft sm:h-49 sm:gap-3.5">
            @foreach ($values as $index => $value)
                <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                    <span class="hidden text-[11.5px] font-bold text-ink-muted sm:block">
                        {{ Number::format($value, $decimals) }}
                    </span>

                    <div
                        @class([
                            'w-full rounded-t-md rounded-b-[2px] bg-linear-to-b sm:rounded-t-lg sm:rounded-b-[3px]',
                            'from-accent to-accent-fade' => $index === $last,
                            'from-bar to-bar-fade' => $index !== $last,
                        ])
                        style="height: {{ round($value / $max * 86) }}%"
                    ></div>
                </div>
            @endforeach
        </div>

        <div class="mt-2.5 flex gap-2 sm:gap-3.5">
            @foreach ($labels as $label)
                <span class="flex-1 text-center text-[10px] font-semibold text-ink-faint sm:text-[11.5px]">{{ $label }}</span>
            @endforeach
        </div>
    </div>
@endif
