<?php

use App\Models\Metric;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Metrics')] class extends Component
{
    public ?int $deletingId = null;

    /**
     * The user's weight measurements, most recent first.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Metric>
     */
    #[Computed]
    public function metrics()
    {
        return auth()->user()->metrics()
            ->orderByDesc('date')
            ->get();
    }

    /**
     * Ask the user to confirm deleting a measurement.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    /**
     * Dismiss the delete confirmation.
     */
    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    /**
     * Delete the measurement the user confirmed.
     */
    public function delete(): void
    {
        $metric = Metric::findOrFail($this->deletingId);

        $this->authorize('delete', $metric);

        $metric->delete();

        $this->deletingId = null;

        unset($this->metrics);
    }
};
?>

<div>
    <x-page-header :title="__('Metrics')">
        <x-slot:actions>
            <x-button :href="route('metrics.create')" as="a" size="icon" wire:navigate aria-label="{{ __('New metric') }}">
                <x-icons.plus />
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        @if ($this->metrics->isEmpty())
            <x-empty-state>{{ __('No measurement recorded yet.') }}</x-empty-state>
        @else
            <x-card>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($this->metrics as $metric)
                        <li wire:key="metric-{{ $metric->id }}" class="flex items-center gap-3 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <span class="block font-medium tabular-nums">
                                    {{ $metric->value_formatted }}
                                    <span class="font-normal text-zinc-500 dark:text-zinc-400">{{ __('kg') }}</span>
                                </span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $metric->date->format(config('dofit.date_format')) }}
                                </span>
                            </div>

                            <x-button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="shrink-0 hover:text-danger"
                                wire:click="confirmDelete({{ $metric->id }})"
                                aria-label="{{ __('Delete metric') }}"
                            >
                                <x-icons.x class="size-4" />
                            </x-button>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>

    <x-confirm-delete :show="$deletingId !== null" :title="__('Delete this measurement?')" />
</div>
