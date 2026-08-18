<?php

use App\Models\Training;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|date')]
    public string $date = '';

    /**
     * Default the date to today.
     */
    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Create the training and open it.
     */
    public function save(): void
    {
        $training = auth()->user()->trainings()->create($this->validate());

        $this->redirect(route('trainings.show', $training), navigate: true);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('New training'));
    }
};
?>

<div>
    <x-page-header :title="__('New training')" :back="route('trainings.index')" />

    <form wire:submit="save">
        <x-field :label="__('Name')" for="name" :error="$errors->first('name')">
            <x-input id="name" type="text" wire:model="name" :invalid="$errors->has('name')" autocomplete="off" autofocus />
        </x-field>

        <x-field :label="__('Date')" for="date" :error="$errors->first('date')">
            <x-input id="date" type="date" wire:model="date" :invalid="$errors->has('date')" />
        </x-field>

        <div class="flex items-center gap-3 pt-2">
            <x-button type="submit" class="flex-1 sm:flex-none">
                {{ __('Save') }}
                <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="save" />
            </x-button>

            <x-button :href="route('trainings.index')" as="a" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </x-button>
        </div>
    </form>
</div>
