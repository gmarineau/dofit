<?php

use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|numeric|min:0')]
    public ?float $value = null;

    /**
     * Record today's weight measurement.
     */
    public function save(): void
    {
        $this->validate();

        auth()->user()->metrics()->create([
            'key' => 'weight',
            'value' => (string) $this->value,
            'date' => now(),
        ]);

        $this->redirect(route('metrics.index'), navigate: true);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('New metric'));
    }
};
?>

<div>
    <x-page-header :title="__('New metric')" :back="route('metrics.index')" />

    <form wire:submit="save">
        <x-field :label="__('Weight')" for="value" :error="$errors->first('value')">
            <x-input id="value" type="number" step="0.1" inputmode="decimal" wire:model="value" :invalid="$errors->has('value')" autocomplete="off" class="numeric text-2xl font-extrabold" autofocus />
        </x-field>

        <div class="flex items-center gap-3 pt-2">
            <x-button type="submit" class="flex-1 sm:flex-none">
                {{ __('Save') }}
                <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="save" />
            </x-button>

            <x-button :href="route('metrics.index')" as="a" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </x-button>
        </div>
    </form>
</div>
