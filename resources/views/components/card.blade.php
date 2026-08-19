@props(['title' => null, 'subtitle' => null, 'icon' => null])

<div {{ $attributes->class('rounded-card border border-line bg-surface p-5 sm:p-6') }}>
    @if ($title)
        <div class="mb-4 flex items-start gap-3 sm:mb-[18px]">
            <div class="min-w-0 flex-1">
                <h2 class="flex items-center gap-1.5 text-[15px] font-semibold tracking-[-0.015em] text-title sm:text-base">
                    @if ($icon)
                        <x-dynamic-component :component="'heroicon-'.$icon" class="size-4 shrink-0" stroke-width="1.7" />
                    @endif

                    {{ $title }}
                </h2>

                @if ($subtitle)
                    <p class="mt-1 text-[12.5px] font-semibold text-ink-muted sm:text-[13.5px]">{{ $subtitle }}</p>
                @endif
            </div>

            {{ $actions ?? '' }}
        </div>
    @endif

    {{ $slot }}
</div>
