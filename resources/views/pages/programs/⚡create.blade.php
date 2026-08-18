<?php

use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    /**
     * Create the program and open it so exercises can be added.
     */
    public function save(): void
    {
        $program = auth()->user()->programs()->create($this->validate());

        $this->redirect(route('programs.edit', $program), navigate: true);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('New program'));
    }
};
?>

<div>
    <x-page-header :title="__('New program')" :back="route('programs.index')" />

    <form wire:submit="save">
        <x-field :label="__('Name')" for="name" :error="$errors->first('name')">
            <x-input id="name" type="text" wire:model="name" :invalid="$errors->has('name')" autocomplete="off" autofocus />
        </x-field>

        <div class="flex items-center gap-3 pt-2">
            <x-button type="submit" class="flex-1 sm:flex-none">
                {{ __('Save') }}
                <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="save" />
            </x-button>

            <x-button :href="route('programs.index')" as="a" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </x-button>
        </div>
    </form>
</div>
