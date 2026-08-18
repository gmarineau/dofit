<?php

use App\Models\Program;
use App\Models\ProgramItem;
use App\Models\ProgramTarget;
use App\Services\ExerciseService;
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

    #[Validate('nullable|integer|min:1|max:255')]
    public ?int $targetSets = null;

    #[Validate('nullable|integer|min:1|max:255')]
    public ?int $targetReps = null;

    #[Validate('nullable|numeric|min:0')]
    public ?float $targetWeight = null;

    /**
     * The exercise a block of sets is being added to, if any.
     */
    public ?int $targetItemId = null;

    /**
     * Whether the exercise search is open.
     */
    public bool $picking = false;

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
        return $this->program->items()->with('exercise', 'targets')->get();
    }

    /**
     * The exercises this user already logged, offered before the search.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Exercise>
     */
    #[Computed]
    public function suggestions()
    {
        return app(ExerciseService::class)->logged(auth()->user());
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
     * Open the exercise search over the program.
     */
    public function pick(): void
    {
        $this->picking = true;
    }

    /**
     * Close the exercise search without adding anything.
     */
    public function closeModal(): void
    {
        $this->picking = false;
    }

    /**
     * Append the chosen exercise, then open its sets form straight away: an
     * exercise without a target is rarely what the user meant to stop at.
     */
    #[On('exercise-chosen')]
    public function addItem(?int $id, string $name): void
    {
        $exercise = app(ExerciseService::class)->resolve(auth()->user(), $id, $name);

        $item = $this->program->items()->create([
            'exercise_id' => $exercise->id,
            'position' => (int) $this->program->items()->max('position') + 1,
        ]);

        $this->picking = false;

        $this->startTarget($item->id);

        unset($this->items, $this->suggestions);
    }

    /**
     * Remove an exercise from the program.
     */
    public function removeItem(int $id): void
    {
        $item = $this->program->items()->findOrFail($id);

        $item->delete();

        if ($this->targetItemId === $id) {
            $this->cancelTarget();
        }

        unset($this->items);
    }

    /**
     * Open the form that adds a block of sets to an exercise.
     */
    public function startTarget(int $id): void
    {
        $this->targetItemId = $this->program->items()->findOrFail($id)->id;

        $this->resetValidation();
    }

    /**
     * Close the block form without adding anything.
     */
    public function cancelTarget(): void
    {
        $this->reset('targetItemId', 'targetWeight');

        $this->resetValidation();
    }

    /**
     * Add another block of sets to an exercise, so it can ask for two sets at
     * 60 kg and two more at 70 kg. The form stays open for the next block.
     */
    public function addTarget(): void
    {
        $this->validate([
            'targetSets' => ['nullable', 'integer', 'min:1', 'max:255'],
            'targetReps' => ['nullable', 'integer', 'min:1', 'max:255'],
            'targetWeight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $item = $this->program->items()->findOrFail($this->targetItemId);

        $item->targets()->create([
            'position' => (int) $item->targets()->max('position') + 1,
            'sets' => $this->targetSets ?? 1,
            'repetition' => $this->targetReps,
            'weight' => $this->targetWeight,
        ]);

        $this->reset('targetWeight');

        unset($this->items);
    }

    /**
     * Remove one block of sets from an exercise.
     */
    public function removeTarget(int $id): void
    {
        ProgramTarget::query()
            ->whereIn('program_item_id', $this->program->items()->select('id'))
            ->findOrFail($id)
            ->delete();

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

    <section class="mb-8">
        <x-section-heading icon="o-pencil-square">{{ __('Name') }}</x-section-heading>

        <x-input wire:model="name" wire:blur="rename" :invalid="$errors->has('name')" />

        <x-error :messages="$errors->first('name')" />
    </section>

    <section>
        <x-section-heading icon="o-rectangle-stack">{{ __('Exercises') }}</x-section-heading>

        @forelse ($this->items as $item)
            @if ($loop->first)
                <ul>
            @endif

            <li wire:key="item-{{ $item->id }}" class="flex items-start gap-3 border-b border-line py-3 last:border-0">
                <span class="numeric mt-1 w-6 shrink-0 text-sm font-bold text-ink-muted">{{ $loop->iteration }}</span>

                <div class="flex min-w-0 flex-1 flex-col items-start">
                    <div class="max-w-full truncate font-bold text-ink">{{ $item->exercise->name }}</div>

                    {{-- One line per block of sets, so 2 × 10 @ 60 and 2 × 10 @ 70 stay separable. --}}
                    @foreach ($item->targets as $target)
                        <div wire:key="target-{{ $target->id }}" class="mt-1 flex items-center gap-2">
                            <span class="numeric text-sm font-semibold text-ink-soft">{{ $target->label }}</span>

                            <x-button
                                type="button"
                                variant="quiet-danger"
                                size="icon-sm"
                                class="size-6"
                                wire:click="removeTarget({{ $target->id }})"
                                aria-label="{{ __('Remove sets') }}"
                            >
                                <x-heroicon-o-trash class="size-3" />
                            </x-button>
                        </div>
                    @endforeach

                    @if ($targetItemId === $item->id)
                        <form wire:submit="addTarget" class="mt-3">
                            <div class="flex items-end gap-2">
                                <x-input type="number" inputmode="numeric" wire:model="targetSets" :placeholder="__('Sets')" class="numeric w-20" />
                                <x-input type="number" inputmode="numeric" wire:model="targetReps" :placeholder="__('Reps')" class="numeric w-20" />
                                <x-input type="number" step="0.1" inputmode="decimal" wire:model="targetWeight" :placeholder="__('Weight')" class="numeric w-24" />

                                <x-button type="submit" size="icon-sm" aria-label="{{ __('Add sets') }}">
                                    <x-heroicon-o-plus class="size-4" />
                                </x-button>

                                <x-button type="button" variant="ghost" size="icon-sm" wire:click="cancelTarget" aria-label="{{ __('Close') }}">
                                    <x-heroicon-o-x-mark class="size-4" />
                                </x-button>
                            </div>

                            <x-error :messages="$errors->first('targetSets') ?: ($errors->first('targetReps') ?: $errors->first('targetWeight'))" />
                        </form>
                    @else
                        <x-add-row
                            as="button"
                            type="button"
                            size="sm"
                            class="mt-2 self-start"
                            wire:click="startTarget({{ $item->id }})"
                            :label="__('Add sets')"
                        />
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
                        <x-heroicon-o-trash class="size-4" />
                    </x-button>
                </div>
            </li>

            @if ($loop->last)
                </ul>
            @endif
        @empty
            <p class="py-4 text-sm font-semibold text-ink-muted">{{ __('No exercise in this program yet.') }}</p>
        @endforelse

        <x-add-row class="mt-4 w-full" as="button" type="button" wire:click="pick" :label="__('Add an exercise')" />
    </section>

    <x-modal :show="$picking" :title="__('Add an exercise')">
        @if ($this->suggestions->isNotEmpty())
            <x-section-heading icon="o-fire">{{ __('Your most used') }}</x-section-heading>

            <div class="mb-5 flex flex-wrap gap-2">
                @foreach ($this->suggestions as $exercise)
                    <button
                        type="button"
                        wire:key="suggestion-{{ $exercise->id }}"
                        wire:click="addItem({{ $exercise->id }}, @js($exercise->name))"
                        class="rounded-full bg-raised px-3 py-1.5 text-sm font-bold text-ink-soft transition hover:bg-accent-soft hover:text-accent"
                    >
                        {{ $exercise->name }}
                    </button>
                @endforeach
            </div>
        @endif

        <livewire:exercise-picker />
    </x-modal>
</div>
