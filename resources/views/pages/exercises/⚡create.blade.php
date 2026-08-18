<?php

use App\Models\Exercise;
use App\Services\ExerciseService;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    /**
     * The muscles the movement works, as picked from the chips.
     *
     * @var list<string>
     */
    public array $muscles = [];

    public ?string $equipment = null;

    /**
     * Add or remove a muscle from the exercise.
     */
    public function toggleMuscle(string $muscle): void
    {
        $this->muscles = in_array($muscle, $this->muscles, true)
            ? array_values(array_diff($this->muscles, [$muscle]))
            : [...$this->muscles, $muscle];
    }

    /**
     * Pick the equipment, or clear it when tapped again.
     */
    public function chooseEquipment(string $equipment): void
    {
        $this->equipment = $this->equipment === $equipment ? null : $equipment;
    }

    /**
     * Create the exercise and open it.
     */
    public function save(): void
    {
        $this->validate();

        $exercise = app(ExerciseService::class)->createCustom(auth()->user(), [
            'name' => $this->name,
            'equipment' => $this->equipment,
            'primary_muscles' => $this->muscles,
        ]);

        $this->redirect(route('exercises.show', $exercise), navigate: true);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('New exercise'));
    }
};
?>

<div>
    <x-page-header
        :title="__('New exercise')"
        :subtitle="__('For a movement the library does not carry.')"
        :back="route('exercises.index')"
    />

    <form wire:submit="save">
        <x-field :label="__('Name')" for="name" :error="$errors->first('name')">
            <x-input id="name" type="text" wire:model="name" :invalid="$errors->has('name')" autocomplete="off" autofocus />
        </x-field>

        <div class="mb-5">
            <span class="mb-2 block text-sm font-bold text-ink-soft">{{ __('Primary muscles') }}</span>

            <div class="flex flex-wrap gap-1.5">
                @foreach (Exercise::MUSCLES as $muscle)
                    <button
                        type="button"
                        wire:key="muscle-{{ $muscle }}"
                        wire:click="toggleMuscle(@js($muscle))"
                        @class([
                            'rounded-full px-2.5 py-1 text-xs font-bold transition',
                            'bg-accent text-accent-ink' => in_array($muscle, $muscles, true),
                            'bg-raised text-ink-soft hover:text-ink' => ! in_array($muscle, $muscles, true),
                        ])
                    >
                        {{ __('muscle.'.$muscle) }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="mb-8">
            <span class="mb-2 block text-sm font-bold text-ink-soft">{{ __('Equipment') }}</span>

            <div class="flex flex-wrap gap-1.5">
                @foreach (Exercise::EQUIPMENTS as $equipmentOption)
                    <button
                        type="button"
                        wire:key="equipment-{{ $equipmentOption }}"
                        wire:click="chooseEquipment(@js($equipmentOption))"
                        @class([
                            'rounded-full px-2.5 py-1 text-xs font-bold transition',
                            'bg-accent text-accent-ink' => $equipment === $equipmentOption,
                            'bg-raised text-ink-soft hover:text-ink' => $equipment !== $equipmentOption,
                        ])
                    >
                        {{ __('equipment.'.$equipmentOption) }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-button type="submit" class="flex-1 sm:flex-none">
                {{ __('Save') }}
                <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="save" />
            </x-button>

            <x-button :href="route('exercises.index')" as="a" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </x-button>
        </div>
    </form>
</div>
