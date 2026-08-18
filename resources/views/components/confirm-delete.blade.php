@props([
    'show' => false,
    'confirm' => 'delete',
    'cancel' => 'cancelDelete',
    'title' => null,
    'message' => null,
])

@if ($show)
    <div
        class="fixed inset-0 z-50 flex items-end justify-center bg-zinc-900/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-delete-title"
        x-on:keydown.escape.window="$wire.{{ $cancel }}()"
    >
        <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl dark:bg-zinc-900">
            <h2 id="confirm-delete-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $title ?? __('Delete this item?') }}
            </h2>

            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $message ?? __('This cannot be undone.') }}
            </p>

            <div class="mt-5 flex gap-3">
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
