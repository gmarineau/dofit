@props(['show' => false, 'title' => null, 'close' => 'closeModal'])

@if ($show)
    {{-- A sheet on phones, a centred dialog from sm up, like the delete confirmation. --}}
    <div
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        x-on:keydown.escape.window="$wire.{{ $close }}()"
        x-on:click.self="$wire.{{ $close }}()"
    >
        <div class="flex max-h-[85vh] w-full flex-col rounded-t-3xl bg-surface sm:max-w-lg sm:rounded-2xl">
            <div class="flex items-center gap-3 border-b border-line px-5 py-4">
                <h2 id="modal-title" class="min-w-0 flex-1 truncate text-lg font-extrabold text-ink">{{ $title }}</h2>

                <x-button type="button" variant="ghost" size="icon-sm" wire:click="{{ $close }}" aria-label="{{ __('Close') }}">
                    <x-heroicon-o-x-mark class="size-4" />
                </x-button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                {{ $slot }}
            </div>
        </div>
    </div>
@endif
