<?php

use App\Models\Training;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Trainings')] class extends Component
{
    public ?int $deletingId = null;

    /**
     * The user's trainings, most recent first.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Training>
     */
    #[Computed]
    public function trainings()
    {
        return auth()->user()->trainings()
            ->withCount('activities')
            ->orderByDesc('date')
            ->get();
    }

    /**
     * Ask the user to confirm deleting a training.
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
     * Delete the training the user confirmed.
     */
    public function delete(): void
    {
        $training = Training::findOrFail($this->deletingId);

        $this->authorize('delete', $training);

        $training->delete();

        $this->deletingId = null;

        unset($this->trainings);
    }
};
?>

<div>
    <x-page-header :title="__('Trainings')">
        <x-slot:actions>
            <x-button :href="route('trainings.create')" as="a" size="icon" wire:navigate aria-label="{{ __('New training') }}">
                <x-icons.plus />
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        @if ($this->trainings->isEmpty())
            <x-empty-state>{{ __('No training recorded yet.') }}</x-empty-state>
        @else
            <x-card>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($this->trainings as $training)
                        <li wire:key="training-{{ $training->id }}" class="flex items-center gap-3 px-4 py-3">
                            <a href="{{ route('trainings.show', $training) }}" wire:navigate class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ $training->name }}</span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $training->activities_formatted }}</span>
                            </a>

                            <span class="shrink-0 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $training->date->format(config('dofit.date_format')) }}
                            </span>

                            <x-button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="shrink-0 hover:text-danger"
                                wire:click="confirmDelete({{ $training->id }})"
                                aria-label="{{ __('Delete training') }}"
                            >
                                <x-icons.x class="size-4" />
                            </x-button>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>

    <x-confirm-delete
        :show="$deletingId !== null"
        :title="__('Delete this training?')"
        :message="__('Its activities and sequences will be deleted too.')"
    />
</div>
