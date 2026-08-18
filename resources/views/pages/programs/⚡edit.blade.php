<?php

use App\Models\Program;
use App\Models\ProgramItem;
use App\Services\ActivityTypeService;
use App\Services\ProgramService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Program $program;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $type = '';

    public ?int $exerciseId = null;

    #[Validate('nullable|integer|min:1|max:255')]
    public ?int $targetSets = null;

    #[Validate('nullable|integer|min:1|max:255')]
    public ?int $targetReps = null;

    #[Validate('nullable|numeric|min:0')]
    public ?float $targetWeight = null;

    /**
     * Load the program the route points at.
     */
    public function mount(Program $program): void
    {
        $this->authorize('update', $program);

        $this->program = $program;
        $this->name = $program->name;
    }

    /**
     * The exercises in the program, in the order they will be performed.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProgramItem>
     */
    #[Computed]
    public function items()
    {
        return $this->program->items()->with('activityType')->get();
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
     * Record the exercise the picker handed over, ready to be given targets.
     */
    #[On('exercise-chosen')]
    public function chooseExercise(?int $id, string $name): void
    {
        $this->exerciseId = $id;
        $this->type = $name;
    }

    /**
     * Rename the program.
     */
    public function rename(): void
    {
        $this->validateOnly('name');

        $this->program->update(['name' => $this->name]);
    }

    /**
     * Append an exercise to the program.
     */
    public function addItem(): void
    {
        $this->validate([
            'type' => ['required', 'string', 'max:255'],
            'targetSets' => ['nullable', 'integer', 'min:1', 'max:255'],
            'targetReps' => ['nullable', 'integer', 'min:1', 'max:255'],
            'targetWeight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $activityType = app(ActivityTypeService::class)
            ->getActivityType(auth()->user(), $this->type, $this->exerciseId);

        $this->program->items()->create([
            'activity_type_id' => $activityType->id,
            'position' => (int) $this->program->items()->max('position') + 1,
            'target_sets' => $this->targetSets,
            'target_reps' => $this->targetReps,
            'target_weight' => $this->targetWeight,
        ]);

        $this->reset('type', 'exerciseId', 'targetWeight');

        unset($this->items, $this->activityTypes);
    }

    /**
     * Remove an exercise from the program.
     */
    public function removeItem(int $id): void
    {
        $item = $this->program->items()->findOrFail($id);

        $item->delete();

        unset($this->items);
    }

    /**
     * Swap an exercise with the one above or below it.
     */
    public function move(int $id, string $direction): void
    {
        $items = $this->program->items()->get();

        $index = $items->search(fn (ProgramItem $item): bool => $item->id === $id);

        $target = $index + ($direction === 'up' ? -1 : 1);

        if ($index === false || $target < 0 || $target >= $items->count()) {
            return;
        }

        [$first, $second] = [$items[$index], $items[$target]];

        [$first->position, $second->position] = [$second->position, $first->position];

        $first->save();
        $second->save();

        unset($this->items);
    }

    /**
     * Turn the program into a fresh training and open it.
     */
    public function start(ProgramService $programs): void
    {
        $this->program->load('items');

        if ($this->program->items->isEmpty()) {
            return;
        }

        $training = $programs->start($this->program);

        $this->redirect(route('trainings.show', $training), navigate: true);
    }

    /**
     * Render the page, showing the program name in the browser tab.
     */
    public function render()
    {
        return $this->view()->title($this->program->name);
    }
};
?>

<div>
    <x-page-header :title="$program->name" :back="route('programs.index')">
        <x-slot:actions>
            <x-button type="button" wire:click="start" :disabled="$this->items->isEmpty()">
                <x-heroicon-o-play class="size-4" />
                {{ __('Start') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <section class="mb-10">
        <x-section-heading>{{ __('Name') }}</x-section-heading>

        <div class="flex items-start gap-3">
            <x-input wire:model="name" wire:blur="rename" :invalid="$errors->has('name')" class="flex-1" />
        </div>

        <x-error :messages="$errors->first('name')" />
    </section>

    <section class="mb-10">
        <x-section-heading>{{ __('Exercises') }}</x-section-heading>

        @forelse ($this->items as $item)
            @if ($loop->first)
                <ul>
            @endif

            <li wire:key="item-{{ $item->id }}" class="flex items-center gap-3 border-b border-line py-3 last:border-0">
                <span class="numeric w-6 shrink-0 text-sm font-bold text-ink-muted">{{ $loop->iteration }}</span>

                <div class="min-w-0 flex-1">
                    <div class="truncate font-bold text-ink">{{ $item->activityType->type }}</div>

                    @if ($item->target_formatted !== '')
                        <div class="numeric mt-0.5 text-sm font-semibold text-ink-soft">{{ $item->target_formatted }}</div>
                    @endif
                </div>

                <div class="flex shrink-0 items-center">
                    <x-button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        wire:click="move({{ $item->id }}, 'up')"
                        :disabled="$loop->first"
                        aria-label="{{ __('Move up') }}"
                    >
                        <x-heroicon-o-arrow-up class="size-4" />
                    </x-button>

                    <x-button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        wire:click="move({{ $item->id }}, 'down')"
                        :disabled="$loop->last"
                        aria-label="{{ __('Move down') }}"
                    >
                        <x-heroicon-o-arrow-down class="size-4" />
                    </x-button>

                    <x-button
                        type="button"
                        variant="quiet-danger"
                        size="icon-sm"
                        wire:click="removeItem({{ $item->id }})"
                        aria-label="{{ __('Remove exercise') }}"
                    >
                        <x-heroicon-o-x-mark class="size-4" />
                    </x-button>
                </div>
            </li>

            @if ($loop->last)
                </ul>
            @endif
        @empty
            <p class="py-6 text-sm font-semibold text-ink-muted">{{ __('No exercise in this program yet.') }}</p>
        @endforelse
    </section>

    <section>
        <x-section-heading>{{ __('Add an exercise') }}</x-section-heading>

        @if ($type === '')
            @if ($this->activityTypes->isNotEmpty())
                <div class="mb-5 flex flex-wrap gap-2">
                    @foreach ($this->activityTypes as $activityType)
                        <button
                            type="button"
                            wire:key="suggestion-{{ $activityType->id }}"
                            wire:click="chooseExercise(null, @js($activityType->type))"
                            class="rounded-full bg-raised px-3 py-1.5 text-xs font-bold text-ink-soft transition hover:bg-accent-soft hover:text-accent"
                        >
                            {{ $activityType->type }}
                        </button>
                    @endforeach
                </div>
            @endif

            <livewire:exercise-picker />
        @else
            <form wire:submit="addItem">
                <div class="mb-5 flex items-center gap-3 rounded-xl bg-raised px-4 py-3">
                    <span class="min-w-0 flex-1 truncate font-bold text-ink">{{ $type }}</span>

                    <x-button type="button" variant="ghost" size="icon-sm" wire:click="$set('type', '')" aria-label="{{ __('Change exercise') }}">
                        <x-heroicon-o-x-mark class="size-4" />
                    </x-button>
                </div>

                <x-error :messages="$errors->first('type')" class="mb-4" />

            <div class="grid grid-cols-3 gap-3">
                <x-field :label="__('Sets')" for="targetSets" :error="$errors->first('targetSets')">
                    <x-input id="targetSets" type="number" inputmode="numeric" wire:model="targetSets" :invalid="$errors->has('targetSets')" class="numeric" />
                </x-field>

                <x-field :label="__('Reps')" for="targetReps" :error="$errors->first('targetReps')">
                    <x-input id="targetReps" type="number" inputmode="numeric" wire:model="targetReps" :invalid="$errors->has('targetReps')" class="numeric" />
                </x-field>

                <x-field :label="__('Weight')" for="targetWeight" :error="$errors->first('targetWeight')">
                    <x-input id="targetWeight" type="number" step="0.1" inputmode="decimal" wire:model="targetWeight" :invalid="$errors->has('targetWeight')" class="numeric" />
                </x-field>
            </div>

                <x-button type="submit" class="w-full sm:w-auto">
                    <x-heroicon-o-plus class="size-4" />
                    {{ __('Add') }}
                </x-button>
            </form>
        @endif
    </section>
</div>
