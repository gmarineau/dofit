<?php

use App\Models\Metric;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
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

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Metrics'));
    }
};
?>

<div>
    <x-page-header :title="__('Metrics')">
        <x-slot:actions>
            <x-button :href="route('metrics.create')" as="a" wire:navigate class="max-sm:hidden">
                <x-heroicon-o-plus class="size-4" />
                {{ __('New metric') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($this->metrics->isNotEmpty())
        <ul>
            @foreach ($this->metrics as $metric)
                <li wire:key="metric-{{ $metric->id }}" class="group flex items-center gap-4 border-b border-line py-4 last:border-0">
                    <div class="flex min-w-0 flex-1 items-baseline gap-1.5">
                        <span class="numeric text-2xl leading-none font-extrabold text-ink">{{ $metric->value_formatted }}</span>
                        <span class="text-sm font-semibold text-ink-muted">{{ __('kg') }}</span>
                    </div>

                    <span class="numeric shrink-0 text-sm font-semibold text-ink-soft">
                        {{ $metric->date->translatedFormat('j M Y') }}
                    </span>

                    <x-button
                            type="button"
                            variant="quiet-danger"
                            size="icon-sm"
                            class="opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 max-sm:opacity-100"
                            wire:click="confirmDelete({{ $metric->id }})"
                            aria-label="{{ __('Delete metric') }}"
                        >
                            <x-heroicon-o-x-mark class="size-4" />
                        </x-button>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state icon="o-scale">
            {{ __('No measurement recorded yet.') }}

            <x-slot:action>
                <x-button :href="route('metrics.create')" as="a" wire:navigate>
                    <x-heroicon-o-plus class="size-4" />
                    {{ __('New metric') }}
                </x-button>
            </x-slot:action>
        </x-empty-state>
    @endif

    <x-fab :href="route('metrics.create')" wire:navigate :label="__('New metric')" />

    <x-confirm-delete :show="$deletingId !== null" :title="__('Delete this measurement?')" />
</div>
