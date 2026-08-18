<?php

use App\Models\Activity;
use App\Services\SequenceService;
use App\Services\SettingService;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Activity $activity;

    #[Validate('required|integer|min:1|max:255')]
    public ?int $repetition = null;

    #[Validate('nullable|numeric|min:0')]
    public ?float $weight = null;

    /**
     * Prefill the form with the user's default repetition count and the weight
     * of the activity's last sequence.
     */
    public function mount(
        Activity $activity,
        SettingService $settings,
        SequenceService $sequences,
    ): void {
        $this->authorize('view', $activity);

        $this->activity = $activity;
        $this->repetition = (int) $settings->get(auth()->user(), 'repetition', '10');
        $this->weight = $sequences->getLastWeight($activity);
    }

    /**
     * Record the sequence and go back to the activity.
     */
    public function save(): void
    {
        $this->activity->sequences()->create($this->validate());

        $this->redirect(route('activities.show', $this->activity), navigate: true);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('New sequence'));
    }
};
?>

<div>
    <x-page-header :title="__('New sequence')" :subtitle="$activity->exercise->name" :back="route('activities.show', $activity)" />

    <form wire:submit="save">
        <div class="grid grid-cols-2 gap-4">
            <x-field :label="__('Weight')" for="weight" :error="$errors->first('weight')">
                <x-input id="weight" type="number" step="0.1" inputmode="decimal" wire:model="weight" :invalid="$errors->has('weight')" autocomplete="off" class="numeric text-2xl font-extrabold" autofocus />
            </x-field>

            <x-field :label="__('Repetition')" for="repetition" :error="$errors->first('repetition')">
                <x-input id="repetition" type="number" inputmode="numeric" wire:model="repetition" :invalid="$errors->has('repetition')" autocomplete="off" class="numeric text-2xl font-extrabold" />
            </x-field>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <x-button type="submit" class="flex-1 sm:flex-none">
                {{ __('Save') }}
                <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="save" />
            </x-button>

            <x-button :href="route('activities.show', $activity)" as="a" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </x-button>
        </div>
    </form>
</div>
