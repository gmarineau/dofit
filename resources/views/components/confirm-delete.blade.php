@props([
    'show' => false,
    'confirm' => 'delete',
    'cancel' => 'cancelDelete',
    'title' => null,
    'message' => null,
])

@if ($show)
    <div
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-delete-title"
        x-on:keydown.escape.window="$wire.{{ $cancel }}()"
        x-on:click.self="$wire.{{ $cancel }}()"
    >
        {{-- A bottom sheet on phones, a centred dialog from sm up. --}}
        <div class="w-full rounded-t-3xl bg-canvas p-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] sm:max-w-sm sm:rounded-2xl sm:pb-6">
            <div class="mx-auto mb-5 h-1 w-10 rounded-full bg-line sm:hidden"></div>

            <h2 id="confirm-delete-title" class="text-lg font-extrabold text-ink">
                {{ $title ?? __('Delete this item?') }}
            </h2>

            <p class="mt-1.5 text-sm font-semibold text-ink-soft">
                {{ $message ?? __('This cannot be undone.') }}
            </p>

            <div class="mt-6 flex gap-3">
                <x-button type="button" variant="secondary" class="flex-1" wire:click="{{ $cancel }}">
                    {{ __('Cancel') }}
                </x-button>

                <x-button type="button" variant="danger" class="flex-1" wire:click="{{ $confirm }}">
                    {{ __('Delete') }}
                </x-button>
            </div>
        </div>
    </div>
@endif
