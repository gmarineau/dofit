<?php

use App\Models\Training;
use App\Services\ActivityTypeService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('New activity')] class extends Component
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
};
?>

<div>
    <x-page-header :title="__('New activity')" :back="route('trainings.show', $training)" />

    <div class="mt-6">
        <x-form-card>
            <form wire:submit="save">
                <x-field :label="__('Type')" for="type" :error="$errors->first('type')">
                    <x-input
                        id="type"
                        type="text"
                        list="activity-types"
                        wire:model="type"
                        :invalid="$errors->has('type')"
                        autocomplete="off"
                        autofocus
                    />

                    <datalist id="activity-types">
                        @foreach ($this->activityTypes as $activityType)
                            <option value="{{ $activityType->type }}"></option>
                        @endforeach
                    </datalist>
                </x-field>

                <div class="flex items-center gap-3">
                    <x-button type="submit">
                        {{ __('Save') }}
                        <x-icons.spinner wire:loading wire:target="save" />
                    </x-button>

                    <a href="{{ route('trainings.show', $training) }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-form-card>
    </div>
</div>
