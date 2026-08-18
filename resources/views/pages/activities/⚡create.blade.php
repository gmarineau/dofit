<?php

use App\Models\Training;
use App\Services\ActivityTypeService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Training $training;

    #[Validate('required|string|max:255')]
    public string $type = '';

    /**
     * Load the training the activity will belong to.
     */
    public function mount(Training $training): void
    {
        $this->authorize('view', $training);

        $this->training = $training;
    }

    /**
     * The activity types already used by this user, offered as suggestions.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityType>
     */
    #[Computed]
    public function activityTypes()
    {
        return app(ActivityTypeService::class)->getUserActivityTypes(auth()->user());
    }

    /**
     * Create the activity, reusing the activity type when it already exists.
     */
    public function save(ActivityTypeService $activityTypes): void
    {
        $this->validate();

        $activityType = $activityTypes->getActivityType(auth()->user(), $this->type);

        $activity = $this->training->activities()->create([
            'activity_type_id' => $activityType->id,
        ]);

        $this->redirect(route('activities.show', $activity), navigate: true);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('New activity'));
    }
};
?>

<div>
    <x-page-header :title="__('New activity')" :back="route('trainings.show', $training)" />

    <form wire:submit="save">
        <x-field :label="__('Type')" for="type" :error="$errors->first('type')">
            <x-input id="type" type="text" wire:model="type" :invalid="$errors->has('type')" autocomplete="off" autofocus />
        </x-field>

        @if ($this->activityTypes->isNotEmpty())
            <div class="mb-5 -mt-2 flex flex-wrap gap-2">
                @foreach ($this->activityTypes as $activityType)
                    <button
                        type="button"
                        wire:key="suggestion-{{ $activityType->id }}"
                        wire:click="$set('type', @js($activityType->type))"
                        class="rounded-full bg-raised px-3 py-1.5 text-xs font-bold text-ink-soft transition hover:bg-accent-soft hover:text-accent"
                    >
                        {{ $activityType->type }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="flex items-center gap-3 pt-2">
            <x-button type="submit" class="flex-1 sm:flex-none">
                {{ __('Save') }}
                <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="save" />
            </x-button>

            <x-button :href="route('trainings.show', $training)" as="a" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </x-button>
        </div>
    </form>
</div>
