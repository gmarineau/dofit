<?php

use App\Models\Activity;
use App\Services\SequenceService;
use App\Services\SettingService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('New sequence')] class extends Component
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
};
?>

<div>
    <x-page-header :title="__('New sequence')" :back="route('activities.show', $activity)" />

    <div class="mt-6">
        <x-form-card>
            <form wire:submit="save">
                <x-field :label="__('Repetition')" for="repetition" :error="$errors->first('repetition')">
                    <x-input id="repetition" type="number" inputmode="numeric" wire:model="repetition" :invalid="$errors->has('repetition')" autocomplete="off" autofocus />
                </x-field>

                <x-field :label="__('Weight')" for="weight" :error="$errors->first('weight')">
                    <x-input id="weight" type="number" step="0.1" inputmode="decimal" wire:model="weight" :invalid="$errors->has('weight')" autocomplete="off" />
                </x-field>

                <div class="flex items-center gap-3">
                    <x-button type="submit">
                        {{ __('Save') }}
                        <x-icons.spinner wire:loading wire:target="save" />
                    </x-button>

                    <a href="{{ route('activities.show', $activity) }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-form-card>
    </div>
</div>
