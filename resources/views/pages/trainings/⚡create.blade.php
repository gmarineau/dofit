<?php

use App\Models\Training;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('New training')] class extends Component
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
};
?>

<div>
    <x-page-header :title="__('New training')" :back="route('dashboard')" />

    <div class="mt-6">
        <x-form-card>
            <form wire:submit="save">
                <x-field :label="__('Name')" for="name" :error="$errors->first('name')">
                    <x-input id="name" type="text" wire:model="name" :invalid="$errors->has('name')" autocomplete="off" autofocus />
                </x-field>

                <x-field :label="__('Date')" for="date" :error="$errors->first('date')">
                    <x-input id="date" type="date" wire:model="date" :invalid="$errors->has('date')" />
                </x-field>

                <div class="flex items-center gap-3">
                    <x-button type="submit">
                        {{ __('Save') }}
                        <x-icons.spinner wire:loading wire:target="save" />
                    </x-button>

                    <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-form-card>
    </div>
</div>
