<?php

use App\Models\Training;
use App\Services\ActivityTypeService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Training $training;

    public string $type = '';

    public ?int $exerciseId = null;

    /**
     * Load the training the activity will belong to.
     */
    public function mount(Training $training): void
    {
        $this->authorize('view', $training);

        $this->training = $training;
    }

    /**
     * The activity types this user already logged, offered as shortcuts.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityType>
     */
    #[Computed]
    public function recentTypes()
    {
        return app(ActivityTypeService::class)->getUserActivityTypes(auth()->user());
    }

    /**
     * Record the exercise the picker handed over.
     */
    #[On('exercise-chosen')]
    public function chooseExercise(?int $id, string $name): void
    {
        $this->exerciseId = $id;
        $this->type = $name;

        $this->save();
    }

    /**
     * Reuse one of the user's own activity types.
     */
    public function chooseType(string $type): void
    {
        $this->exerciseId = null;
        $this->type = $type;

        $this->save();
    }

    /**
     * Create the activity and open it.
     */
    public function save(): void
    {
        $this->validate(['type' => ['required', 'string', 'max:255']]);

        $activityType = app(ActivityTypeService::class)
            ->getActivityType(auth()->user(), $this->type, $this->exerciseId);

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

    <x-error :messages="$errors->first('type')" class="mb-4" />

    @if ($this->recentTypes->isNotEmpty())
        <section class="mb-8">
            <x-section-heading>{{ __('Your exercises') }}</x-section-heading>

            <div class="flex flex-wrap gap-2">
                @foreach ($this->recentTypes as $activityType)
                    <button
                        type="button"
                        wire:key="recent-{{ $activityType->id }}"
                        wire:click="chooseType(@js($activityType->type))"
                        class="rounded-full bg-raised px-3 py-1.5 text-sm font-bold text-ink-soft transition hover:bg-accent-soft hover:text-accent"
                    >
                        {{ $activityType->type }}
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <x-section-heading>{{ __('Pick an exercise') }}</x-section-heading>

        <livewire:exercise-picker />
    </section>
</div>
